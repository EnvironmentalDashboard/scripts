#!/bin/bash

fn=`date -d "today" +"%Y_%m_%d_%H:%M"`
mysqldump -ucron_tim -p93XWLYMfOiIcEvXo oberlin_environmentaldashboard > /root/db_backups/oberlin_environmentaldashboard/db_backup.sql 2> /dev/null
mv /root/db_backups/oberlin_environmentaldashboard/db_backup.sql "/root/db_backups/oberlin_environmentaldashboard/$fn.sql"
mysqldump -ucron_tim -p93XWLYMfOiIcEvXo community_voices > /root/db_backups/community_voices/db_backup.sql 2> /dev/null
mv /root/db_backups/community_voices/db_backup.sql "/root/db_backups/community_voices/$fn.sql"
