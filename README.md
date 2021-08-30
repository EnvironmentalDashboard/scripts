# Scripts

This repo contains various scripts that don't really belong in a repository of their own.

## Refreshing SSL Certificate

In the common case, the Environmental Dashboard SSL certificate can be refreshed once securely logging into the sever and navigating to the scripts repository currently found at /var/repos/scripts then by running `./bundle_ssl.sh`, followed by `./sync_ssl.sh`, and finally running `service haproxy restart`.
