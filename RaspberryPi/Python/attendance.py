import picamera
import sys
import time
import os

# How many pictures to take
n_images = 180

camera = picamera.PiCamera()

try:
    class_id = sys.argv[1]
    session = sys.argv[2]

    ###
    path = '/home/pi/check/' + class_id + "_" + session

    for i in range(n_images):
        file_name = class_id + '_' + str(i) + '.jpg'
        file_path = os.path.join(path, file_name)
        camera.capture(file_path)
        # Setting time between pictures
        time.sleep(1)

finally:
    camera.close()