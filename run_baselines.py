import numpy as np
from important_functions import *




#===============================================================
#PARAMETERS
#===============================================================

data_files = ["Data/new_edm_5","Data/new_edm_9","Data/new_edm_18","Data/CC_enron","Data/CC_yeast"]
data_names = ["EDM_SMALL", "EDM_MEDIUM", "EDM_BIG", "CC_ENRON", "CC_YEAST"]
data_sizes = [[750,3000],[750,3000],[750,3000],[3000],[7500,15000]]
seed = 1

#MLPs
layers = [(100,)]
alphas = [0.0001]
#RNN
enc_units = [10]



#===============================================================
#EXPERIMENTS WITH HYPERPARAMETER GRIDSEARCH
#===============================================================

rnn_results = []
one_hot_results = []
graph_results = []
naive_results = []

rnn_params = []
one_hot_params = []
graph_params = []

for data_file, data_size_list in zip(data_files,data_sizes):
    for data_size in data_size_list:
        #LOAD DATA SET
        print(data_file,data_size)
        X_train = np.load("{}_survey_{}_X_train.npy".format(data_file,data_size))
        X_test = np.load("{}_survey_{}_X_test.npy".format(data_file,data_size))
        X_val = np.load("{}_survey_{}_X_val.npy".format(data_file,data_size))
        y_train = np.load("{}_survey_{}_y_train.npy".format(data_file,data_size))
        y_test = np.load("{}_survey_{}_y_test.npy".format(data_file,data_size))
        y_val = np.load("{}_survey_{}_y_val.npy".format(data_file,data_size))
        
        if len(y_train.shape) > 1:
            y_train = np.ravel(y_train)
            y_val = np.ravel(y_val)
            y_test = np.ravel(y_test)
            
        #NAIVE BASELINE    
        print("Naive Baseline          ",end="\r")
        y_mean = np.mean(y_train)
        y_pred = np.array([y_mean for _ in y_test])
        naive_results.append(y_pred)
            
            
        #RNN
        print("One-Hot-RNN               ",end="\r")
        X_b = preprocess_sequence_one_hot(X_train)
        X_val_b = preprocess_sequence_one_hot(X_val)
        X_test_b = preprocess_sequence_one_hot(X_test)

        best_params = None
        best_mae = 10000
        for layer in layers:
            for enc_unit in enc_units:
                mae = val_rnn(enc_unit,layer,X_b,y_train,X_val_b,y_val,seed)
                if mae < best_mae:
                    best_mae = mae
                    best_params = [layer,enc_unit]

        y_pred = run_rnn(best_params[1],best_params[0],X_b,y_train,X_test_b,seed)
        rnn_results.append(y_pred)
        rnn_params.append(best_params)
        
        
        #One-Hot
        print("One-Hot               ",end="\r")
        X_b = preprocess_one_hot(X_train)
        X_val_b = preprocess_one_hot(X_val)
        X_test_b = preprocess_one_hot(X_test)

        best_params = None
        best_mae = 10000
        for layer in layers:
            for alpha in alphas:
                mae = val_mlp(1,[layer,alpha,'relu'],X_b,y_train,X_val_b,y_val)
                if mae < best_mae:
                    best_mae = mae
                    best_params = [layer,alpha,'relu']

        y_pred = run_mlp(1,best_params,X_b,y_train,X_test_b)
        one_hot_results.append(y_pred)
        one_hot_params.append(best_params)
        

        #Graph
        print("Graph               ",end="\r")
        X_g = preprocess_edges(X_train)
        X_val_g = preprocess_edges(X_val)
        X_test_g = preprocess_edges(X_test)

        best_params = None
        best_mae = 10000
        for layer in layers:
            for alpha in alphas:
                mae = val_mlp(1,[layer,alpha,'relu'],X_g,y_train,X_val_g,y_val)
                if mae < best_mae:
                    best_mae = mae
                    best_params = [layer,alpha,'relu']

        y_pred = run_mlp(1,best_params,X_g,y_train,X_test_g)
        graph_results.append(y_pred)
        graph_params.append(best_params)
        
        print(" RNN: ",rnn_params[-1], " one-hot: ",
              one_hot_params[-1]," graph: ",graph_params[-1])
        
        for y_pred, algorithm in zip([naive_results[-1],rnn_results[-1],one_hot_results[-1],graph_results[-1]],
                                     ["naive baseline","RNN","one-hot","graph"]):
            print_results(y_pred, y_test, algorithm)
            
            
            
            