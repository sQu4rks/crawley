import requests
import queue
import threading
import queue
import re
import time
import sys

from bs4 import BeautifulSoup

import tldextract

class ParseUrlThread(threading.Thread):
    def __init__(self, queue_in, queue_out):
        threading.Thread.__init__(self)
        self.queue_in = queue_in
        self.queue_out = queue_out

    def parse(self,raw):
        soup = BeautifulSoup(raw, 'html.parser')

        found_links = []
        for a in soup.find_all('a'):
            found = str(a.get('href'))

            if re.match("https?://.*", found):
                extracted = tldextract.extract(found)
                domain = ''

                if extracted.subdomain is not "":
                    domain += extracted.subdomain 
                    domain += "."
                if extracted.domain is not "":
                    domain += extracted.domain
                    domain += "."
                if extracted.suffix is not "":
                    domain += extracted.suffix

                if domain not in found_links:
                    found_links.append(domain)
        generator = ''
        for m in soup.find_all('meta'):
            if m.get('name') == 'generator':
                raw_generator = m.get('content')
                generator = raw_generator.replace("\n"," ")
        return generator, found_links
    def run(self):
        while True:
            host = self.queue_in.get()
            #print("Requested " + host + " with " + str(self.queue_in.qsize()) + " left")
            status = -1
            http_available = False
            https_available = False
            redirect = False
            https_links = []
            http_links = []
            merged_links = []
            
            http_gen = ''
            https_gen = ''
            
            generator = ''
            # Do requests and get status
            headers = {
			    'User-Agent': 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13'
			}
            try:
                requests.packages.urllib3.disable_warnings()
                r = requests.get("https://{}".format(host),timeout=5,verify=False,headers=headers)
                if r.status_code == 200:
                    https_available = True
                    https_gen, https_links = self.parse(r.text)
            except Exception as e:
            	pass
                #print("Failed to establish https connection for " + host)
            try:
                r = requests.get("http://{}".format(host),timeout=5,verify=False,headers=headers)
                if r.status_code == 200:
                    http_available = True
                    http_gen, http_links = self.parse(r.text)

                if r.url.startswith("https:"):
                    redirect = True
            except Exception as e:
            	pass
                #print("Failed to establish http connection for " + host)

            # Calculate status
            if redirect:
                status = 0
            elif http_available and not https_available:
                status = 1
            elif http_available and https_available:
                status = 2
            # Merge links
            merged_links = https_links

            for l in http_links:
                if l not in merged_links:
                    merged_links.append(l)

            if len(https_gen) > len(http_gen):
                generator = https_gen
            else:
                generator = http_gen
            self.queue_in.task_done()
            self.queue_out.put({'host': host, 'status': status, 'links': merged_links,'generator' : generator, 'time': int(time.time())})

def get_list(host, token):
    req_url = "https://{}/api.php?token={}&action=gettask".format(host, token)
    urls = []
    chunk_id = -1
    try:
        r = requests.get(req_url)

        if r.status_code == 200:
            lines = r.text.split("\n")
            chunk_id = lines[0].replace("\n","")
            if chunk_id == "<br />":
                print(r.text)
            for i in range(1, len(lines)):
                urls.append(lines[i].replace("\n",""))
    except Exception as e:
        print(str(e))

    return chunk_id, urls

def post_results(host, token, chunk_id, results):
    req_url = "https://crawl.hashes.org/api.php?token={}&action=sendtask".format(token)
    
    result_string = ''
    collected_string = ''
    idx = 0
    numRef = 0
    for res in results:
        result_string += str(res['host']) + "," + str(res['status']) + "," + str(res['time']) + "," + str(res['generator']) 
        if idx == len(results) - 1:
            pass
        else:
            result_string += "\n"
        for found in res['links']:
            collected_string += str(found) + "," + str(res['host']) + "\n"
            numRef = numRef + 1
        idx = idx + 1
    try:
        r = requests.post(req_url, data={'chunk': chunk_id, 'result': result_string, 'collected': collected_string},verify=False)
        print("For chunk " + chunk_id + " got " + r.text + " - " + str(numRef) + " References")
    except Exception as e:
        print(" ")
        print(" ")
        print(str(e))
if __name__ == "__main__":
    token = str(sys.argv[1])
    host = "crawl.hashes.org"

    chunk_id, urls = get_list(host, token)
    print("Got chunk id: " + chunk_id)
    #print("got urls" + str(urls))
    queue_in = queue.Queue()
    queue_out = queue.Queue()
    
    for i in range(5):
        t = ParseUrlThread(queue_in, queue_out)
        t.setDaemon(True)
        t.start()

    for host in urls:
        queue_in.put(host)

    queue_in.join()
    
    output = []
    while queue_out.empty() == False:
        output.append(queue_out.get())
    post_results(host, token, chunk_id, output)