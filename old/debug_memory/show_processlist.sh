#!/bin/bash

MEM=`free -m | awk 'NR==2{printf "%d\n", $3*100/$2 }'`
MEM=$(($MEM+0))
if [ $MEM -gt 85 ]; then
  END=$((`date +%s`+45))
  FN=`date +%Y-%m-%d:%H:%M`
  while [ $END -gt `date +%s` ]; do
    mysql -u tim -ppTMVx?4HP3D7PEJp -e "SHOW FULL PROCESSLIST;" >> "/root/mem_log/$FN.log"
    DATE=`date`
    printf "\n\nCompleted on $DATE\n\n\n\n" >> "/root/mem_log/$FN.log"
    sleep .5
  done
fi