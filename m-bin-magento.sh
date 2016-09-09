#!/usr/bin/env bash

arguments=$@
docker exec -it --privileged -u magento2 magento2-devbox-web php /var/www/magento2/bin/magento $arguments
