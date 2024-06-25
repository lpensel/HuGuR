import numpy as np

from sklearn.metrics import mean_absolute_error
from sklearn.metrics import r2_score
from sklearn.metrics import mean_squared_error
from sklearn.neural_network import MLPRegressor


import logging
logging.getLogger("tensorflow").setLevel(logging.ERROR) # this goes *before* tf import

import tensorflow as tf
from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import LSTM, Dense
tf.autograph.set_verbosity(0)

#==============================================
#Preprocessing Permutations
#==============================================

#Turn permutation in one-hot encoded sequence
def preprocess_sequence_one_hot(X):
    X_out = []
    n = X.shape[1]
    for x in X:
        x_out = []
        for i in x:
            x_cur = np.zeros((n))
            x_cur[i] = 1
            x_out.append(x_cur)
        X_out.append(np.array(x_out))
    return np.array(X_out).astype(np.float32)

#Turn permutation in one-hot encoded vector
def preprocess_one_hot(X):
    X_out = []
    n = X.shape[1]
    for x in X:
        x_out = np.zeros((n*n))
        for i in range(n):
            x_out[i*n+x[i]] = 1
        X_out.append(x_out)
    return np.array(X_out)

#Turn permutation in graph encoding
def preprocess_edges(X,n=None):
    X_out = []
    if n is None:
        n = X.shape[1]
    for x in X:
        cur_X = np.zeros(n**2 + 2*n)
        cur_X[n**2+x[0]] = 1
        cur_X[n**2+n+x[-1]] = 1
        for u,v in zip(x[:-1],x[1:]):
            cur_X[u*n+v] = 1
        X_out.append(cur_X)
    return np.array(X_out)



#==================================================
#SOME HANDY FUNCTIONS
#==================================================

#Print result metrics
def print_results(y_pred,y_true,algorithm):
    mae = mean_absolute_error(y_true, y_pred)
    mse = mean_squared_error(y_true, y_pred)
    r2 = r2_score(y_true, y_pred)
    print("{}:: MAE:{:.3e}   MSE:{:.3e}   R^2:{:.3e}".format(algorithm,mae,mse,r2))




#===================================================
#RNN
#===================================================

#Building the RNN
@tf.autograph.experimental.do_not_convert
def build_rnn(n,enc_units,layers,seed):
    model = Sequential()
    model.add(LSTM(units=enc_units, return_sequences=False, input_shape=(n, n),
                   kernel_initializer=tf.keras.initializers.GlorotUniform(seed=seed)))
    for layer in layers:
        model.add(Dense(units=layer, activation='relu',kernel_initializer=tf.keras.initializers.GlorotUniform(seed=seed)))
    model.add(Dense(units=1,kernel_initializer=tf.keras.initializers.GlorotUniform(seed=seed)))
    model.compile(loss='mean_absolute_error', optimizer='adam')
    
    return model

#Evaluating RNN parameters for gridsearch
@tf.autograph.experimental.do_not_convert
def val_rnn(enc_units,layers,X_train,y_train,X_val,y_val,seed):
    n = X_train.shape[-1]
    model = build_rnn(n, enc_units, layers,seed)
    model.fit(X_train,y_train,epochs=200,verbose=0,batch_size=200)
    y_pred = model.predict(X_val,verbose=0)
    mae = mean_absolute_error(y_val, y_pred)
    return mae

#Train and predict data with RNN
@tf.autograph.experimental.do_not_convert
def run_rnn(enc_units,layers,X_train,y_train,X_test,seed):
    n = X_train.shape[-1]
    model = build_rnn(n, enc_units, layers,seed)
    model.fit(X_train,y_train,epochs=200,verbose=0,batch_size=200)
    y_pred = model.predict(X_test,verbose=0)
    return y_pred


#===================================================
#MLP
#===================================================

#Evaluate MLP parameters for gridsearch
def val_mlp(seed,params,X_train,y_train,X_test,y_test,verbose=False):
    model = MLPRegressor(random_state=seed,early_stopping=True,hidden_layer_sizes=params[0], alpha=params[1], activation=params[2])
    model.fit(X_train,y_train)
    y_pred = model.predict(X_test)
    mae = mean_absolute_error(y_test,y_pred)
    return mae

#Train and predict data with MLP
def run_mlp(seed,params,X_train,y_train,X_test):
    model = MLPRegressor(random_state=seed,early_stopping=True,hidden_layer_sizes=params[0], alpha=params[1], activation=params[2])
    model.fit(X_train,y_train)
    y_pred = model.predict(X_test)
    return y_pred