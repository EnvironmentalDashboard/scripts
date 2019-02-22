#!/bin/bash

cat /etc/letsencrypt/live/oberlindashboard.org/fullchain.pem /etc/letsencrypt/live/oberlindashboard.org/privkey.pem > /etc/ssl/haproxy/oberlindashboard.org.pem
cat /etc/letsencrypt/live/environmentalorb.org/fullchain.pem /etc/letsencrypt/live/environmentalorb.org/privkey.pem > /etc/ssl/haproxy/environmentalorb.org.pem
cat /etc/letsencrypt/live/environmentaldashboard.org/fullchain.pem /etc/letsencrypt/live/environmentaldashboard.org/privkey.pem > /etc/ssl/haproxy/environmentaldashboard.org.pem