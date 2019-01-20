#!/bin/bash

# find will return a newline if ip.cache younger than 1 day, filename if older than 1 day
find "${BASH_SOURCE%/*}/ip.cache" -mmin +1440 2>/dev/null | grep -q '[^[:space:]]'
res=$?
if [ $res -eq 0 ]; then # ip.cache contains non-whitespace charachters ie file is older than 1 day
  curl -s http://ipecho.net/plain > "${BASH_SOURCE%/*}/ip.cache"
fi
ip=`cat "${BASH_SOURCE%/*}/ip.cache"`
if [ "$ip" == "159.89.232.129" ]; then
  rsync -azP /var/www/uploads nyc1@ajlc.csr.oberlin.edu:/var/www/
elif [ "$ip" == "132.162.36.210" ]; then
  rsync -azP /var/www/uploads ajlc-csr@159.89.232.129:/var/www/
fi
