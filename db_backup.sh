#!/bin/bash

fn=`date -d "today" +"%Y_%m_%d_%H:%M"`
`mysqldump -u root -pxvyb7g88 oberlin_environmentaldashboard > /root/db_backups/db_backup.sql 2> /dev/null`
`mv /root/db_backups/db_backup.sql /root/db_backups/$fn.sql`
