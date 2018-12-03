#!/bin/bash
# this script is meant to find specific regular files (i.e. images) in a backup folder and move them to the server if they don't exist already
# reads list of files to find from stdin; reads directory to move files to as first cli argument
# usage: `cat list_of_files | ./restore_files.sh /var/www/uploads/CV_Media/images/`
# the above command will fetch each file specified in list_of_files (1 per line)
# from the backup server (currently hardcoded) and place them in /var/www/uploads/CV_Media/images/
# use the -f flag to overwrite files in target_dir if it already exists

while read line
do
	argumentArray=(. -name "$line")
	res=`find "${argumentArray[@]}"` # the exact location of file in backup folders based on name
	lns=`echo "$res" | wc -l`
	if [ $lns -eq 1 ]; then # only 1 file found
		printf "$res => $1\n"
		argumentArray=("$res" "root@159.89.232.129:$1")
		scp "${argumentArray[@]}"
	else
		printf "Multiple files:\n$res"
	fi
done < /dev/stdin

