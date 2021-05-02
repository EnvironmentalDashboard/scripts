# Scripts

This repo contains various scripts that don't really belong in a repository of their own.

## Refreshing SSL Certificate

In the common case, the Environmental Dashboard SSL certificate can be refreshed by running `./bundle_ssl.sh` here, then `./sync_ssl.sh` here, then
running `service haproxy restart`.
