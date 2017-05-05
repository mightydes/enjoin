#!/usr/bin/env bash

SYNC_DIR=/vagrant
USER_DIR=/var/www
PROV_DIR=/vagrant/dev/provisioning
DEFAULT_DIR=$PROV_DIR/default
PLACE_DIR=$PROV_DIR/place
FQDN=vagrant.enjoin.dev

PACKAGES=(
    git
    g++
    python-software-properties
)

PHP5_PACKAGES=(
    php5-common
    php5-cli
    php5-curl
    php5-json
    php5-intl
    php5-mysql
    php5-mcrypt
    php5-gd
    php5-redis
    php5-xmlrpc
    php5-xsl
)

PHP7_PACKAGES=(
    php7.0
    php7.0-mysql
    php7.0-mbstring
    php7.0-intl
    php7.0-xmlrpc
    php7.0-xsl
)

MYSQL_ROOT_PWD=enjoin_test
MYSQL_USER=enjoin_test
MYSQL_USER_PWD=enjoin_test
MYSQL_DB_LIST=(
    enjoin_test
)
