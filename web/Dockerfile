# 1. linux, php 70 [x]
# 2. mounted directory [x]
# 3. node, gulp, grunt,
# 4. blackfireio,
# 5. email-write-to-file, phpunit, composer, xdebug

FROM php:7.0.9-apache
MAINTAINER "Oleksandr Iegorov <oiegorov@magento.com>"

RUN  apt-get update && apt-get install -y \
    curl \
    libmcrypt-dev \
    libicu-dev \
    libxml2-dev libxslt1-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng12-dev \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) mcrypt intl xsl gd zip pdo_mysql \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN a2enmod rewrite

# Site config
ADD default.conf /etc/apache2/sites-enabled/default.conf
RUN rm -f /etc/apache2/sites-enabled/000-default.conf

# Magento installation
ADD auth.json /root/.composer/auth.json
RUN cd /var/www/magento2 && composer create-project --repository-url="https://repo.magento.com/" magento/project-community-edition .

EXPOSE 80
CMD ["apache2-foreground"]