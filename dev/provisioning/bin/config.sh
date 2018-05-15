#!/usr/bin/env bash

SYNC_DIR=/vagrant
USER_DIR=/var/www
PROV_DIR=/vagrant/dev/provisioning
DEFAULT_DIR=$PROV_DIR/default
PLACE_DIR=$PROV_DIR/place
FQDN=vagrant.enjoin.test

MYSQL_VER=5.6
PHP_VER=5.5

PACKAGES=(
    git
    g++
    python-software-properties
)

MYSQL_ROOT_PWD=enjoin_test
SQL_USER=enjoin_test
SQL_USER_PWD=enjoin_test
SQL_DB_LIST=(
    enjoin_test
)
