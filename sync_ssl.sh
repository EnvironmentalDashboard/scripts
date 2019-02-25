#!/bin/bash

./bundle_ssl.sh
scp -r /etc/ssl/haproxy root@ajlc.csr.oberlin.edu:/etc/ssl
echo "Updated SSL certificates" | mail -s "Server message" dashboard@oberlin.edu
