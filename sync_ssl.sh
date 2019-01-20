#!/bin/bash

scp -r /etc/letsencrypt/live nyc1@ajlc.csr.oberlin.edu:/etc/letsencrypt/
scp /etc/letsencrypt/options-ssl-apache.conf nyc1@ajlc.csr.oberlin.edu:/etc/letsencrypt/
echo "Updated SSL certificates" | mail -s "Server message" trobertf@oberlin.edu
