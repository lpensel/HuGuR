import numpy as np

from scipy.optimize import least_squares
from sklearn.metrics import r2_score
from sklearn.metrics import mean_absolute_error
from sklearn.metrics import mean_squared_error
import time

from sklearn.model_selection import KFold
from sklearn.linear_model import LinearRegression
from sklearn.linear_model import Ridge


import pickle


class rule_item(object):
    def __init__(self,name,rule,X_rule,beta,level,prev_rule=None,next_rule=None):
        self.name = name
        self.rule = rule
        self.X_rule = X_rule
        self.beta = beta 
        self.level = level 
        self.active = True
        self.next_rule = next_rule
        self.prev_rule = prev_rule
        

        
class simple_learner(object):
    def __init__(self,X,y,num_rules=10,learning_rate=0.1,beta_type="gradboost",seed=None,verbose=False):
        self.X = X
        self.X_loc = np.argsort(self.X)
        self.y = y
        self.learning_rate = learning_rate
        self.verbose = verbose
        self.beta_type = beta_type
        
        self.rng = np.random.default_rng(seed)

        self.base_predict = np.mean(self.y)

        self.X_rules = []
        self.beta = []
        self.rule_list = []
        self.n = self.X.shape[1]
        self.m = self.X.shape[0]
        self.num_rules = num_rules

        self.cur_rule_name = 0
        self.structure = {}


        self.base_rules = {}    
        for i in range(self.n):
            for j in range(self.n):
                if i!=j:
                    rule = [i,j]
                    X_rule = self.gen_x_rule_base_(rule)
                    self.base_rules[tuple(rule)] = X_rule
        

        
        
    def check_rule_(self,rule,x):
        return int(np.all(np.greater([x[r2]-x[r1] for r1,r2 in zip(rule[:-1],rule[1:])],[0])))

    def gen_x_rule_base_(self,rule,parent=None):
        if parent is None:
            return np.array([[self.check_rule_(rule,x)] for x in self.X_loc])
        else:
            selected = np.where(parent == 1)[0]
            out = np.zeros((self.X.shape[0],1))
            out[selected] = np.array([[self.check_rule_(rule,x)] for x in self.X_loc[selected]])
            
    def gen_x_rule_(self,rule):
        out = np.ones((self.X.shape[0],1))
        for i,j in zip(rule[:-1],rule[1:]):
            out = out * self.base_rules[(i,j)]
        return out

    
    def search(self,residuals,rule_base):
        taus = []
        rules = []
        
        for rule in rule_base:
            X_rule = self.base_rules[rule]
            gradient = np.sum(residuals*X_rule)
            taus.append(abs(gradient))
            rules.append(rule)
        best_rules = [rules[i] for i in np.argsort(taus)[::-1][:self.num_rules]]

        return best_rules

    def build_lists(self):
        self.rule_list = []
        self.X_rules = []
        self.beta = []

        current_rule = self.structure[0]
        while current_rule is not None:
            if current_rule.active:
                self.rule_list.append(current_rule.rule)
                self.X_rules.append(current_rule.X_rule)
                self.beta.append(current_rule.beta)

            current_rule = current_rule.next_rule

    def calc_beta_ridge(self,rule_list,residuals):
        X_rules = [self.base_rules[rule] for rule in rule_list]
        X_rule_data = np.concatenate(X_rules,axis=1)
        #reg = LinearRegression(fit_intercept=False).fit(X_rule_data, residuals)
        reg = Ridge(alpha=1.0,fit_intercept=False).fit(X_rule_data, residuals)
        if np.isscalar(reg.coef_[0]):
            beta = [e for e in reg.coef_]
        else:
            beta = [e for e in reg.coef_[0]]

        return beta

    def one_gradboost_search_(self,rule_list,residuals):
        best_rule = 0
        X_rule = self.base_rules[rule_list[0]]
        tau = np.sum(residuals*X_rule)
        
        
        for idx,rule in enumerate(rule_list):
            X_rule = self.base_rules[rule]
            gradient = np.sum(residuals*X_rule)
            if abs(gradient) > abs(tau):
                best_rule = idx
                tau = gradient
        return best_rule, tau

    def calc_beta_gradboost(self,rule_list,residuals):
        beta = []
        cur_residuals = np.copy(residuals)
        cur_rule_list = [rule for rule in rule_list]
        new_rule_list = []


        while len(cur_rule_list) >= 1:
            cur_rule_idx, gradient = self.one_gradboost_search_(cur_rule_list,cur_residuals)
            cur_rule = cur_rule_list[cur_rule_idx]
            cur_X_rule = self.base_rules[cur_rule]
            if np.sum(cur_X_rule) == 0:
                new_beta = 0.
            else:
                new_beta = self.learning_rate * (gradient/np.sum(cur_X_rule))
            beta.append(new_beta)
            new_rule_list.append(cur_rule)
            cur_residuals -= (new_beta * cur_X_rule)
            cur_rule_list = cur_rule_list[:cur_rule_idx] + cur_rule_list[cur_rule_idx+1:]
        return beta, new_rule_list
 
            
    def train(self):
        residuals = self.y - (np.ones_like(self.y) * self.base_predict)
        loss = np.sum(np.square(residuals))
        
        rule_list = self.search(residuals,list(self.base_rules.keys()))
        if self.beta_type == "ridge":
            beta = self.calc_beta_ridge(rule_list,residuals)
        if self.beta_type == "gradboost":
            beta, rule_list = self.calc_beta_gradboost(rule_list,residuals)
        X_rules = [self.base_rules[rule] for rule in rule_list]


        self.structure[self.cur_rule_name] = rule_item(self.cur_rule_name,rule_list[0],X_rules[0],beta[0],1,None,None)
        self.cur_rule_name += 1
        for idx in range(len(rule_list)-1):
            self.structure[self.cur_rule_name] = rule_item(self.cur_rule_name,rule_list[idx+1],X_rules[idx+1],beta[idx+1],1,self.structure[self.cur_rule_name-1],None)
            self.structure[self.cur_rule_name-1].next_rule = self.structure[self.cur_rule_name]
            self.cur_rule_name += 1

        self.build_lists()


    def get_children(self,rule):
        children = []
        base_rule = list(rule)
        for item in range(self.n):
            if item in base_rule:
                continue
            for idx in range(len(base_rule)+1):
                child = tuple(base_rule[:idx] + [item] + base_rule[idx:])
                if child not in self.base_rules:
                    X_child = self.gen_x_rule_(child)
                    self.base_rules[child] = X_child
                children.append(child)
        return children


    def deepen(self,pivot):

        if len(self.structure[pivot].rule) >= self.n:
            return
        self.structure[pivot].active = False
        self.build_lists()

        predictions = (np.ones_like(self.y) * self.base_predict)
        for rule, X_rule, beta in zip(self.rule_list,self.X_rules,self.beta):
            predictions += (X_rule * beta)
        residuals = self.y - predictions



        children = self.get_children(self.structure[pivot].rule)
        new_rules = self.search(residuals,children)
        
        if self.beta_type == "ridge":
            beta = self.calc_beta_ridge(new_rules,residuals)
        if self.beta_type == "gradboost":
            beta, new_rules = self.calc_beta_gradboost(new_rules,residuals)
        X_rules = [self.base_rules[rule] for rule in new_rules]




        pivot_next_rule = self.structure[pivot].next_rule
        new_level = self.structure[pivot].level + 1

        self.structure[self.cur_rule_name] = rule_item(self.cur_rule_name,new_rules[0],X_rules[0],beta[0],new_level,self.structure[pivot],None)
        self.structure[pivot].next_rule = self.structure[self.cur_rule_name]
        self.cur_rule_name += 1
        for idx in range(len(new_rules)-1):
            self.structure[self.cur_rule_name] = rule_item(self.cur_rule_name,new_rules[idx+1],X_rules[idx+1],beta[idx+1],new_level,self.structure[self.cur_rule_name-1],None)
            self.structure[self.cur_rule_name-1].next_rule = self.structure[self.cur_rule_name]
            self.cur_rule_name += 1
        self.structure[self.cur_rule_name-1].next_rule = pivot_next_rule



        self.build_lists()


    def flatten(self,pivot):
        self.structure[pivot].active = True

        current_rule = self.structure[pivot].next_rule
        while current_rule is not None and current_rule.level > self.structure[pivot].level:
            current_rule.active = False
            current_rule = current_rule.next_rule
        self.structure[pivot].next_rule = current_rule

        self.build_lists()

    def simplify(self):
        beta_abs = [abs(b) for b in self.beta]
        best_rules = [self.rule_list[i] for i in np.argsort(beta_abs)[::-1][:self.num_rules]]

        self.cur_rule_name = 0
        self.structure = {}
        residuals = self.y - (np.ones_like(self.y) * self.base_predict)
        loss = np.sum(np.square(residuals))
        
        if self.beta_type == "ridge":
            beta = self.calc_beta_ridge(best_rules,residuals)
        if self.beta_type == "gradboost":
            beta, best_rules = self.calc_beta_gradboost(best_rules,residuals)
        X_rules = [self.base_rules[rule] for rule in best_rules]


        self.structure[self.cur_rule_name] = rule_item(self.cur_rule_name,best_rules[0],X_rules[0],beta[0],1,None,None)
        self.cur_rule_name += 1
        for idx in range(len(best_rules)-1):
            self.structure[self.cur_rule_name] = rule_item(self.cur_rule_name,best_rules[idx+1],X_rules[idx+1],beta[idx+1],1,self.structure[self.cur_rule_name-1],None)
            self.structure[self.cur_rule_name-1].next_rule = self.structure[self.cur_rule_name]
            self.cur_rule_name += 1

        self.build_lists()

            
            
    def predict(self,X):
        X_loc = np.argsort(X)
        X_test = [np.array([[self.check_rule_(rule,x)] for x in X_loc])
                   for rule in self.rule_list]
        #X_test = np.concatenate(X_test,axis=1)
        y_predict = np.ones((X.shape[0],1)) * self.base_predict
        #y_predict + np.dot(X_test,self.beta)[:,np.newaxis]
        for beta,X_rule in zip(self.beta,X_test):
                y_predict += (X_rule * beta)
        return y_predict

    def detailed_predict(self,x):
        x_loc = np.argsort(x)
        x_test = [self.check_rule_(rule,x_loc)
                   for rule in self.rule_list]
        #X_test = np.concatenate(X_test,axis=1)
        y_predict = 1. * self.base_predict
        #y_predict + np.dot(X_test,self.beta)[:,np.newaxis]
        active_rules = []
        for idx,(beta,x_rule) in enumerate(zip(self.beta,x_test)):
                y_predict += (x_rule * beta)
                if x_rule == 1:
                    active_rules.append(idx)
        return y_predict, active_rules

    def get_complexity(self):
        complexity = 0.
        for rule in self.rule_list:
            complexity += len(rule)/self.n
        return complexity
    
    
    def validate(self,X,y):
        y_pred = self.predict(X)
        mae = mean_absolute_error(y, y_pred)
        
        return mae

    