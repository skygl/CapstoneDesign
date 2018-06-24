#! /usr/bin/python

try:
	import paramiko
	import sys
	import time
	import subprocess
	import os
	import pandas as pd
	from sklearn.pipeline import Pipeline
	from sklearn.lda import LDA
	from sklearn.preprocessing import LabelEncoder
	from sklearn.svm import SVC
	import pickle
	from operator import itemgetter
except Exception as e:
	pass

#라즈베리파이 ssh 접속

ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy)
###
ssh.connect('192.168.43.153', username='pi', password='raspberry')
#ssh.connect('192.168.0.203', username='pi', password='raspberry')
#ssh.connect('172.30.1.30', username='pi', password='raspberry')


# Input : Student ID
id = sys.argv[1]

# Raspberry Pi local path will have aligned images
###
PI_PATH = '/home/pi/aligned/' + id
# Ubuntu local path will have aligned images
###
SERVER_PATH = '/home/jh/aligned_image/' + id
os.system("mkdir "+SERVER_PATH)
command_mkdir = 'mkdir ' + PI_PATH
ssh.exec_command(command_mkdir)
sftp = ssh.open_sftp()
# How many pictures to take for embedding
n_pictures = 30

# Run Raspberry Pi Python Code Remotely
###
command_take = 'python3 /home/pi/register.py ' + id
print(command_take)
stdin, stdout, stderr = ssh.exec_command(command_take)
# Wait for End of Command Execution
# It Print Pictures' File Path
# Read Names and Copy them to Server
if(stdout.channel.recv_exit_status() == 0):
	file_list = stdout.readlines()
	for i in range(0, n_pictures):
		origin = file_list[i][:-1]
		file_name = id + '_' + str(i) + '.png'
		copy = os.path.join(SERVER_PATH, file_name)
		sftp.get(origin, copy)

# Every Time Execute Lua File, You Have to Remove "cache.t7" File!
###
cache_path = '/home/jh/aligned_image/cache.t7'
if(os.path.exists(cache_path)):
	print("Cache File is Deleted")
	os.remove(cache_path)

# Start Embedding

# Execute Lua File
# You should write '/home/jh/torch/install/bin/th'. - If you not, an Error may occur !
# Data Directory Must Have the Following Structure
# Base Directory
# ├── First Person
# │   ├── picture_1.jpg
# │   ├── picture_2.jpg
# │   ├── ...
# │   └── picture_n.jpg
# ├──  ...
# │
# └── Last Person
#     ├── picture_1.jpg
#     ├── picture_2.jpg
#     ├── ...
#     └── picture_n.jpg

###
cmd = '/home/jh/torch/install/bin/th /home/jh/torch/openface/batch-represent/main.lua -outDir /home/jh/embedding_image/ -data /home/jh/aligned_image/'
os.system(cmd)

# Load Embedding

#print("Loading Embeddings\n")
###
fname = '/home/jh/embedding_image/labels.csv'
labels = pd.read_csv(fname, header = None).as_matrix()[:, 1]
labels = map(itemgetter(1),map(os.path.split, map(os.path.dirname, labels)))
###
fname = '/home/jh/embedding_image/reps.csv'
embeddings = pd.read_csv(fname, header = None).as_matrix()
labels = list(labels)
le = LabelEncoder().fit(labels)
labelsNum = le.transform(labels)
nClasses = len(le.classes_)
print("Training for {} classes".format(nClasses))

# Training
clf = SVC(C=1, kernel='linear', probability=True)
clf_final = clf
clf = Pipeline([('lda', LDA(n_components=1)), ('clf', clf_final)])
clf.fit(embeddings, labelsNum)

# Save Result File
###
fName = '/home/jh/embedding_image/classifier.pkl'
print("'Saving Classifier to '{}'".format(fName))
with open(fName, 'wb') as f:
	pickle.dump((le, clf), f)