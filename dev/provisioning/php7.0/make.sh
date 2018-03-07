#!/usr/bin/env bash

apt-get install -y \
    php7.0 \
    php7.0-mysql \
    php7.0-mbstring \
    php7.0-intl \
    php7.0-xmlrpc \
    php7.0-xsl

default_file /etc/php/7.0/cli/php.ini "php$PHP_VER/cli-php.ini"
