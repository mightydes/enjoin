#!/usr/bin/env bash

SYNC_DIR=/vagrant
USER_DIR=/var/www
PROV_DIR=/vagrant/dev/provision
FQDN=vagrant.enjoin.test

NODEJS_VER=6.x
REDIS_VER=3.2
PHP_VER=7.1
PERCONA_VER=5.5
MYSQL_VER=8.0
PGSQL_VER=9.x

PACKAGES=(
    nano
    git
    g++
    python-software-properties
)

MYSQL_ROOT_PWD=`get_env ENJ_PASSWORD`
SQL_USER=`get_env ENJ_USERNAME`
SQL_USER_PWD=`get_env ENJ_PASSWORD`
SQL_DB_LIST=(
    `get_env ENJ_DATABASE`
)
