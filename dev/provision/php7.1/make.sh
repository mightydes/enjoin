#!/usr/bin/env bash

PHP_PACKAGES=(
    php71u-cli
    php71u-common
    php71u-intl
    php71u-json
    php71u-mbstring
    php71u-mysqlnd
    php71u-mysqlnd
    php71u-pgsql
    php71u-opcache
    php71u-pdo
    php71u-xml
    php71u-xmlrpc
)

yum -y install ${PHP_PACKAGES[@]}
yellow "Configure PHP"
default_file /etc/php.ini php$PHP_VER/php.ini
place_file   php$PHP_VER/php.ini /etc/php.ini
