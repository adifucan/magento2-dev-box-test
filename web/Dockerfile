FROM php:7.0.10-fpm
MAINTAINER "Oleksandr Iegorov <oiegorov@magento.com>"

RUN adduser --disabled-password --gecos "" magento2

RUN apt-get update && apt-get install -y \
    apt-utils \
    cron \
    curl \
    libmcrypt-dev \
    libicu-dev \
    libxml2-dev libxslt1-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng12-dev \
    git \
    vim \
    openssh-server \
    supervisor \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) mcrypt intl xsl gd zip pdo_mysql opcache \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && docker-php-ext-install opcache \
    && pecl install xdebug && docker-php-ext-enable xdebug \
    && echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_port=9000" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_connect_back=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.max_nesting_level=1000" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && mkdir /var/run/sshd

RUN apt-get clean && apt-get update && apt-get install -y nodejs \
    && ln -s /usr/bin/nodejs /usr/bin/node

RUN apt-get clean && apt-get update && apt-get install -y npm \
    && npm update -g npm && npm install -g grunt-cli && npm install -g gulp

RUN docker-php-ext-install bcmath

RUN echo "StrictHostKeyChecking no" >> /etc/ssh/ssh_config

# PHP config
ADD php.ini /usr/local/etc/php

# SSH config
ADD sshd_config /etc/ssh/sshd_config
ADD supervisord.conf /etc/supervisord.conf
RUN passwd -d magento2

ADD magento2.conf /usr/local/etc/php-fpm.d/magento2.conf
RUN echo "[magento2]" >> /usr/local/etc/php-fpm.d/docker.conf
RUN echo "access.log = /proc/self/fd/2" >> /usr/local/etc/php-fpm.d/docker.conf
RUN echo "clear_env = no" >> /usr/local/etc/php-fpm.d/docker.conf
RUN echo "catch_workers_output = yes" >> /usr/local/etc/php-fpm.d/docker.conf
RUN apt-get install -y apache2
RUN a2enmod rewrite
RUN a2enmod proxy
RUN a2enmod proxy_fcgi
ADD default.conf /etc/apache2/sites-enabled/default.conf
RUN rm -f /etc/apache2/sites-enabled/000-default.conf

# Initial scripts
COPY scripts/ /home/magento2/scripts/
RUN chown -R magento2:magento2 /home/magento2

RUN curl -sS https://accounts.magento.cloud/cli/installer | php
ENV PATH /root/.magento-cloud/bin:$PATH
WORKDIR  /home/magento2

RUN service cron restart

EXPOSE 80 22 44100
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisord.conf"]
