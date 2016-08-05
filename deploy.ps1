$deploy = "cd /var/www/magento2 && composer create-project --repository-url=""https://repo.magento.com/"" magento/project-community-edition ."
Set-Content ../magento2/deploy.sh $deploy
docker exec -it magento2-devbox-web /bin/sh /var/www/magento2/deploy.sh