#!/bin/bash
ip=`curl -s http://ipecho.net/plain`
if [ "$ip" == "159.89.232.129" ]; then
  rsync -azP /var/www/uploads nyc1@ajlc.csr.oberlin.edu:/var/www/
elif [ "$ip" == "132.162.36.210" ]; then
  rsync -azP /var/www/uploads ajlc-csr@159.89.232.129:/var/www/
fi
