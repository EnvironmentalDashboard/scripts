#!/bin/bash

# set the backup location
cd /root/db_backups/oberlin_environmentaldashboard
#disable sending mail after every backup, if enable it again remove the MAILTO=""
MAILTO=""
# backup the database
fn=`date -d "today" +"%Y_%m_%d_%H_%M"`
mysqldump -ucron_tim -p93XWLYMfOiIcEvXo oberlin_environmentaldashboard > db_backup.sql 2> /dev/null
#compress the sql file into a tar
tar -czvf $fn.tar.gz db_backup.sql
# remove the sql file once the compression is completed
rm db_backup.sql

#no need to export the community voices now
# cd /root/db_backups/community_voices
# mysqldump -ucron_tim -p93XWLYMfOiIcEvXo community_voices > db_backup.sql 2> /dev/null
# tar -czvf $fn.tar.gz db_backup.sql
# remove the sql file once the compression is completed
# rm db_backup.sql
