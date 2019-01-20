#!/bin/bash

fn="${BASH_SOURCE%/*}/ip.cache"
# if ls says cache is missing curl it
# if its not, find will return filename if ip.cache modification >1 day, grep for whitespace returned otherwise
ls "$fn" > /dev/null 2>&1 || true && \
find "$fn" -mmin +1440 2>/dev/null | grep -q '[^[:space:]]'
res=$?
# ip.cache contains non-whitespace charachters ie file is older than 1 day
if [ $res -eq 0 ]; then
  curl -s http://ipecho.net/plain > "$fn"
fi
ip=`cat "$fn"`
if [ "$ip" == "159.89.232.129" ]; then
  rsync -az /var/www/uploads nyc1@ajlc.csr.oberlin.edu:/var/www/
elif [ "$ip" == "132.162.36.210" ]; then
  rsync -az /var/www/uploads ajlc-csr@159.89.232.129:/var/www/
fi
