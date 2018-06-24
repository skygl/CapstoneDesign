try:
	import paramiko
	import sys
	import time
	import subprocess
	import os
	import openface
	import pickle
	import numpy as np
	import cv2
	import pymysql
	import random

except Exception as e:
	pass


# Get List of Face Info
def getRep(imgPath, num, multiple = True):
	bgrImg = cv2.imread(imgPath)
	sreps = []
	if bgrImg is None:
		print("Unable to load image : {}\n".format(imgPath))
		cv2.imwrite(Detected + str(num) + ".jpg", bgrImg)
		return sreps

	rgbImg = cv2.cvtColor(bgrImg, cv2.COLOR_BGR2RGB)

	bbs = align.getAllFaceBoundingBoxes(rgbImg)

	if len(bbs) == 0:
		print('Unable to find a face : {}\n'.format(imgPath))
		cv2.imwrite(Detected + str(num) + ".jpg", bgrImg)
		return sreps
	reps = []
	for bb in bbs:
		alignedFace = align.align(96, rgbImg, bb, landmarkIndices=openface.AlignDlib.OUTER_EYES_AND_NOSE)
		if alignedFace is None:
			cv2.imwrite(Detected + str(num) + ".jpg", bgrImg)
			print('Unable to align image : {}\n'.format(imgPath))
			return sreps
		rep = net.forward(alignedFace)
		reps.append((bb.center().x, rep))
	for bb in bbs:
		cv2.rectangle(bgrImg, (bb.left(), bb.bottom()), (bb.right(), bb.top()), (0, 255, 0), 2)
	cv2.imwrite(Detected + str(num) + ".jpg", bgrImg)
	print(str(num)+".jpg")
	sreps = sorted(reps, key=lambda x: x[0])
	return sreps

# Making dictionary with all registered student to count how many pictures to detect for each student
###
aligned_path = '/home/jh/aligned_image'
person_dict = {}
for dir in os.listdir(aligned_path):
	person_dict[dir] = 0

# Determine how many detected pictures will change attendance from absence to presence
threshold = 0.05
# How many pictures to take ( default Pi code takes 1 sec for 1 picture )
n_images = 180

###
txt_f = open('/home/jh/test.txt', 'w')
ssh = paramiko.SSHClient()
ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy)
###
#ssh.connect('192.168.0.203', username='pi', password='raspberry')
#ssh.connect('172.30.1.30', username = 'pi', password = 'raspberry')
ssh.connect('192.168.43.153', username='pi', password='raspberry')
conn = pymysql.connect(host='localhost', user='root', password='', db='attendance')
curs = conn.cursor(pymysql.cursors.DictCursor)

class_id = sys.argv[1]
session = sys.argv[2]

# Raspberry Pi local path will have images
###
PI = '/home/pi/check/' + class_id + '_' + session
###
# Server local path will have images
SERVER = '/home/jh/check/' + class_id + '_' + session
###
# Server local path will have images with rectangles for each detected person's face
###
Detected = '/home/jh/detected/' + class_id + "_" + session +'/'
classifer_model_path = '/home/jh/embedding_image/classifier.pkl'
os.system('chmod 777 '+SERVER+'/*')
os.system('mkdir ' + Detected)

os.system('mkdir ' + SERVER)
os.system('chmod 777 ' + SERVER)
command_mkdir = 'mkdir ' + PI
ssh.exec_command(command_mkdir)
command_chmod = 'chmod 777 ' + PI
ssh.exec_command(command_chmod)
sftp = ssh.open_sftp()

# Run Raspberry Pi Python Code Remotely
###
command_take = 'python3 /home/pi/attendance.py ' + class_id + ' ' + session
stdin, stdout, stderr = ssh.exec_command(command_take)
# Wait for End of Command Execution
if(stdout.channel.recv_exit_status() == 0):
	for i in range(n_images):
		file_name = class_id + '_' + str(i) + '.jpg'
		origin = os.path.join(PI, file_name)
		copy = os.path.join(SERVER, file_name)
		sftp.get(origin, copy)

###
align = openface.AlignDlib('/home/jh/torch/openface/models/dlib/shape_predictor_68_face_landmarks.dat')
###
net = openface.TorchNeuralNet('/home/jh/torch/openface/models/openface/nn4.small2.v1.t7', 96, cuda = False)

with open(classifer_model_path, 'rb') as f:
	(le, clf) = pickle.load(f, encoding='latin1')

	for i in range(n_images):
		file_path = os.path.join(SERVER, class_id + '_' + str(i) + '.jpg')
		print(file_path)
		reps = getRep(file_path, i, multiple = True)
		print(file_path+'\n')
		if len(reps) > 1:
			print("List of Faces in Image from Left to Right\n")
		one_time_person = []
		for r in reps:
			rep = r[1].reshape(1, -1)
			bbx = r[0]
			predictions = clf.predict_proba(rep).ravel()
			maxI = np.argmax(predictions)
			person = le.inverse_transform(maxI)
			confidence = predictions[maxI]
			# If confidence > 0.75, count for the person in dictionary add 1.
			if confidence > 0.75:
				# If same person is detected in the same picture, Ignore
				if person in one_time_person:
					print("Find Dupliated Face\n");
				else:
					one_time_person.append(person)
					person_dict[person] = person_dict[person]+1
			print('Predict {} @ x={} with {:.2f} confidence\n'.format(person, bbx, confidence))

sql = """select name from class where id=%s"""
curs.execute(sql, (class_id))
rows = curs.fetchall()
class_name = rows[0]['name']
print("Class Name : "+class_name+"\n")
sql = """select id from user where class=%s and role=%s"""
curs.execute(sql, (class_name, "student"))
rows = curs.fetchall()
student_list = []
for element in rows:
	print(str(element['id'])+"\n")
	student_list.append(str(element['id']))
sql = """insert into attendance(studentid, classid, session, ispresent) values (%s, %s, %s, %s)"""
person_keys = person_dict.keys()
for keys in person_keys:
	if keys in student_list:
		print(keys + ' - ' + str(person_dict[keys]))
		txt_f.write(keys + ' - ' + str(person_dict[keys]) + '\n')
		# Delete existing DB and Insert presence query
		if person_dict[keys] >= (n_images*threshold):
			delete_sql = """delete from attendance where studentid=%s and classid=%s and session=%s"""
			curs.execute(delete_sql, (int(keys), int(class_id), int(session)))
			curs.execute(sql, (int(keys), int(class_id), int(session), 1))
		conn.commit()
txt_f.close()


