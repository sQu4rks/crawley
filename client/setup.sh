sudo apt-get update
sudo apt-get install git python3-pip libffi-dev php5-cli php5-curl libssl-dev htop

git clone https://seinlc@bitbucket.org/ugglyafinc/crawly.git 
cd crawly
sudo pip3 install -r requirements.txt 
sudo pip3 install tldextract

php -f wrapper.php $1 $2
python3 runner_$1.py