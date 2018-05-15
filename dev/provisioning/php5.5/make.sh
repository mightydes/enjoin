#!/usr/bin/env bash

apt-get install -y \
    php5-common \
    php5-cli \
    php5-curl \
    php5-json \
    php5-intl \
    php5-mysql \
    php5-pgsql \
    php5-mcrypt \
    php5-gd \
    php5-redis \
    php5-xmlrpc \
    php5-xsl

default_file /etc/php5/cli/php.ini "php$PHP_VER/cli-php.ini"
