#!/bin/bash

cd /root/
rsync -azP /var/www/uploads/ steve@ajlc.csr.oberlin.edu:/home/steve/backups/uploads
rsync -azP /etc/apache2/sites-available/ steve@ajlc.csr.oberlin.edu:/home/steve/backups/server-config/sites-available
rsync -azP /etc/ssl/ steve@ajlc.csr.oberlin.edu:/home/steve/backups/server-config/ssl
rsync -azP /root/db_backups/ steve@ajlc.csr.oberlin.edu:/home/steve/backups/db_backups
crontab -l > /root/crontab_backup && rsync -azP /root/crontab_backup steve@ajlc.csr.oberlin.edu:/home/steve/backups/server-config/crontab
apt-clone clone pckgs && rsync -azP /root/pckgs.apt-clone.tar.gz steve@ajlc.csr.oberlin.edu:/home/steve/backups/apt/pckgs.apt-clone.tar.gz # https://unix.stackexchange.com/a/208163
rsync -azP /var/www/PHPMailer/ steve@ajlc.csr.oberlin.edu:/home/steve/backups/mail-server/PHPMailer/
rsync -azP /etc/opendkim/ steve@ajlc.csr.oberlin.edu:/home/steve/backups/mail-server/opendkim/
rsync -azP /var/secret/ steve@ajlc.csr.oberlin.edu:/home/steve/backups/secret