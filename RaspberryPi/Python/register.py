import picamera
import sys
import time
import os
import cv2
import numpy as np
import random
import shutil
import openface
import openface.helper
from openface.data import iterImgs

camera = picamera.PiCamera()

try:
    id = sys.argv[1]
    ###
    raw_path = '/home/pi/raw/' + id
    ###
    aligned_path = '/home/pi/aligned/' + id
    openface.helper.mkdirP(raw_path)
    openface.helper.mkdirP(aligned_path)
    # How many pictures to take for resgister
    n_pictures = 30
    
    
    # Delete files, If aligned_path has files
    for file in os.listdir(aligned_path):
            file_path = os.path.join(aligned_path, file)
            if os.path.isfile(file_path):
                os.unlink(file_path)
        
    landmarkIndices = openface.AlignDlib.OUTER_EYES_AND_NOSE
    ###
    align = openface.AlignDlib(os.path.join('/home/pi/distro/openface/models/dlib', "shape_predictor_68_face_landmarks.dat"))
    i = 0
    img_count = 0
    # img_count - How many pictures having detected face
    while img_count < n_pictures:
        for j in range(0, (n_pictures-img_count)):
            raw_file_name = id + "_" + str(i) + ".jpg"
            i += 1
            raw_file_path = os.path.join(raw_path, raw_file_name)
            camera.capture(raw_file_path)
            time.sleep(1)
        imgs = list(iterImgs(raw_path))
        random.shuffle(imgs)
        
        # Detect Face in pictures
        for imgObject in imgs:
            imgName = os.path.join(aligned_path, imgObject.name) + ".png"
            rgb = imgObject.getRGB()
            if rgb is None:
                outRgb = None
            else:
                outRgb = align.align(96, rgb, landmarkIndices = landmarkIndices, skipMulti=True)
            if outRgb is not None:
                outBgr = cv2.cvtColor(outRgb, cv2.COLOR_RGB2BGR)
                cv2.imwrite(imgName, outBgr)
                print(imgName)
                img_count += 1
                
        # Delete raw files
        for file in os.listdir(raw_path):
            file_path = os.path.join(raw_path, file)
            if os.path.isfile(file_path):
                os.unlink(file_path)
finally:
    camera.close()

