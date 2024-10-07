#!/bin/bash

# set the backup location
cd /root/db_backups/

fn=`date -d "today" +"%Y_%m_%d_%H_%M"`
mysqldump -ucron_tim -p93XWLYMfOiIcEvXo oberlin_environmentaldashboard > oberlin_environmentaldashboard/db_backup.sql 2> /dev/null
#compress the sql file into a tar 
tar -czvf $fn.tar.gz oberlin_environmentaldashboard/db_backup.sql
# remove the sql file once the compression is completed
rm oberlin_environmentaldashboard/db_backup.sql

#no need to export the community voices now
# mysqldump -ucron_tim -p93XWLYMfOiIcEvXo community_voices > community_voices/db_backup.sql 2> /dev/null
# tar -czvf $fn.tar.gz community_voices/db_backup.sql
# remove the sql file once the compression is completed
# rm community_voices/db_backup.sql