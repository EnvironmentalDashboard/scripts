#!/bin/bash

cd /root/
apt-clone clone pckgs && rsync -azP /root/pckgs.apt-clone.tar.gz steve@ajlc.csr.oberlin.edu:/home/steve/backups/pckgs.apt-clone.tar.gz # https://unix.stackexchange.com/a/208163
crontab -l > /root/crontab_backup && rsync -azP /root/crontab_backup steve@ajlc.csr.oberlin.edu:/home/steve/backups/crontab
rsync -azP /var/www/uploads/ steve@ajlc.csr.oberlin.edu:/home/steve/backups/uploads
rsync -azP /etc/ steve@ajlc.csr.oberlin.edu:/home/steve/backups/etc
rsync -azP /root/db_backups/ steve@ajlc.csr.oberlin.edu:/home/steve/backups/db_backups
rsync -azP /var/www/PHPMailer/ steve@ajlc.csr.oberlin.edu:/home/steve/backups/PHPMailer
rsync -azP /var/secret/ steve@ajlc.csr.oberlin.edu:/home/steve/backups/secret
