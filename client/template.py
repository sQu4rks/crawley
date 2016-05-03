#!/usr/bin/python

import threading
import time
import subprocess
import datetime

exitFlag = 0

class myThread (threading.Thread):
    def __init__(self, threadID, name, counter, token):
        threading.Thread.__init__(self)
        self.threadID = threadID
        self.name = name
        self.counter = counter
        self.tok = token
    def run(self):
    	while True:
	        print ("Starting " + self.name)
	        startTime = int(time.time())
	        subprocess.call(["python3", "crawly.py", self.tok])
	        totalTime = int(time.time()) - startTime
	        print("Finished " + self.name + " -  used " + str(totalTime) + "s")


# Create and start new threads
__THREADS__

print("Exiting Main Thread")