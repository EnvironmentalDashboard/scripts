#!/bin/bash

cd /root/backup_files # assuming the files from /home/steve/backups/ are placed in /root/backup_files

sudo apt-get install apt-clone
sudo apt-clone restore pckgs.apt-clone.tar.gz # this will reinstall all packages

cat crontab | crontab -

mkdir -P /var/www/uploads/
mkdir /var/www/repos
mkdir /var/repos

mv uploads /var/www/uploads
mv etc /etc
mv secret /var/secret

cd /var/www/repos
git clone https://github.com/EnvironmentalDashboard/buildingnavigation.git
git clone https://github.com/EnvironmentalDashboard/calendar.git
git clone https://github.com/EnvironmentalDashboard/community-voices.git
git clone https://github.com/EnvironmentalDashboard/environmentaldashboard.org.git
git clone https://github.com/EnvironmentalDashboard/citywide-dashboard.git && mv citywide-dashboard cwd
git clone https://github.com/EnvironmentalDashboard/chart.git
git clone https://github.com/EnvironmentalDashboard/prefs.git
git clone https://github.com/EnvironmentalDashboard/includes.git
git clone https://github.com/EnvironmentalDashboard/fault-detection.git
git clone https://github.com/EnvironmentalDashboard/GoogleDriveAPI.git
git clone https://github.com/EnvironmentalDashboard/orb-server.git
git clone https://github.com/EnvironmentalDashboard/gauges.git
git clone https://github.com/EnvironmentalDashboard/orbs.git
# git clone https://github.com/EnvironmentalDashboard/search.git # not currently used

cd /var/repos
git clone https://github.com/EnvironmentalDashboard/scripts.git
git clone https://github.com/EnvironmentalDashboard/buildingos-api-daemons.git
git clone https://github.com/EnvironmentalDashboard/relative-value.git

ln -s /var/www/repos/environmentaldashboard.org /var/www/environmentaldashboard.org
ln -s /var/www/repos/orb-server /var/www/environmentalorb.org
ln -s /usr/share/phpmyadmin /var/www/phpmyadmin
ln -s /var/www/repos/environmentaldashboard.org /var/www/repos/environmentaldashboard.org/oberlin
ln -s /var/www/repos/orbs /var/www/repos/environmentaldashboard.org/orb-scripts
ln -s /var/www/repos/GoogleDriveAPI /var/www/repos/environmentaldashboard.org/google-drive
ln -s /var/www/uploads /var/www/repos/environmentaldashboard.org/images/uploads
ln -s /var/www/repos/buildingnavigation /var/www/repos/environmentaldashboard.org/time-series
ln -s /var/www/repos/prefs /var/www/repos/environmentaldashboard.org/prefs
ln -s /var/www/repos/calendar /var/www/repos/environmentaldashboard.org/calendar
ln -s /var/www/repos/environmentaldashboard.org /var/www/repos/environmentaldashboard.org/ben-franklin
ln -s /var/www/repos/cwd /var/www/repos/environmentaldashboard.org/cwd-files
ln -s /var/www/repos/gauges /var/www/repos/environmentaldashboard.org/gauges
ln -s /var/www/repos/environmentaldashboard.org /var/www/repos/environmentaldashboard.org/toledo
ln -s /var/www/repos/chart /var/www/repos/environmentaldashboard.org/chart
ln -s /var/www/repos/calendar /var/www/repos/environmentaldashboard.org/symlinks/oberlin.org/calendar
ln -s /var/www/repos/calendar /var/www/repos/environmentaldashboard.org/symlinks/ben-franklin/calendar
ln -s /var/www/repos/community-voices/vendor/phpunit/phpunit/phpunit /var/www/repos/community-voices/vendor/bin/phpunit
ln -s /var/www/uploads/calendar /var/www/repos/calendar/images/uploads
ln -s /var/www/repos/includes /var/repos/includes

cd /var/repos/buildingos-api-daemons
docker build -t buildingosd .
docker run -dit -p 52001:3306 -v /root:/root --restart always --name buildingosd1 buildingosd
docker run -dit -p 52002:3306 -v /root:/root --restart always --name buildingosd2 buildingosd

cd /var/www/repos/community-voices
docker build -t community-voices .
docker run -dit -p 3002:80 --restart always -v /var/www/uploads/CV_Media/images/:/var/www/uploads/CV_Media/images/ -v $(pwd):/var/www/html/ -e "MYSQL_HOST=159.89.232.129" -e "MYSQL_DB=community_voices" -e "MYSQL_USER=public_cv" -e "MYSQL_PASS=1234" -e SERVER=`hostname` --name PROD_CV community-voices

cd /var/www/repos/fault-detection
docker build -t fault-detection-image .
docker run -p 3001:80 --restart always --name fault-detection-container -v $(pwd):/src -dit fault-detection-image


