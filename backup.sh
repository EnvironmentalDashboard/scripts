#!/bin/bash

# list user installed apt packageshttps://askubuntu.com/a/492343
comm -23 <(apt-mark showmanual | sort -u) <(gzip -dc /var/log/installer/initial-status.gz | sed -n 's/^Package: //p' | sort -u) > /root/pckgs && rsync -az /root/pckgs nyc1@ajlc.csr.oberlin.edu:/home/nyc1/backups/pckgs # https://unix.stackexchange.com/a/208163
# backup crontab
crontab -l > /root/crontab_backup && rsync -az /root/crontab_backup nyc1@ajlc.csr.oberlin.edu:/home/nyc1/backups/crontab
# backing up uploads is taken care of by sync_static_assets.sh
#rsync -az /var/www/uploads/ nyc1@ajlc.csr.oberlin.edu:/home/nyc1/backups/uploads
# server configs
rsync -az /etc/ nyc1@ajlc.csr.oberlin.edu:/home/nyc1/backups/etc
# db backups
rsync -az /root/db_backups/ nyc1@ajlc.csr.oberlin.edu:/home/nyc1/backups/db_backups
# db credentials, other secrets
rsync -az /var/secret/ nyc1@ajlc.csr.oberlin.edu:/home/nyc1/backups/secret
