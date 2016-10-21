#!/usr/bin/env bash

SYNC_DIR=/vagrant
USER_DIR=/var/www
DEFAULT_DIR=vagrant/default
PLACE_DIR=vagrant/place
FQDN=vagrant.enjoin

PACKAGES=(
    git
    g++
    python-software-properties
)

PHP_PACKAGES=(
    php5-common
    php5-cli
    php5-curl
    php5-json
    php5-intl
    php5-mysql
    php5-mcrypt
    php5-gd
    php5-memcached
    php5-xmlrpc
    php5-xsl
)

MYSQL_ROOT_PWD=enjoin_test
MYSQL_USER=enjoin_test
MYSQL_USER_PWD=enjoin_test
MYSQL_DB_LIST=(
    enjoin_test
)
