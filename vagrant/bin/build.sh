#!/usr/bin/env bash

set -v

# Go to script directory
BASE_DIR=$(dirname $0)
cd $BASE_DIR

# Get config
source config.sh
source functions.sh

# Update repos / timezone / locales:
update_locales
apt-get update

# Create user directory via link:
rm -rf $USER_DIR
ln -fs $SYNC_DIR $USER_DIR

# Install packages:
for pkg in ${PACKAGES[@]}
do
  install_package $pkg
done

# NodeJS friends:
add-apt-repository -y ppa:chris-lea/node.js
apt-get update
install_package nodejs
npm install -g n
n 4.4.2
npm install -g gulp

# Install MySQL:
silent_install_package mysql-server
mysqladmin -u root password $MYSQL_ROOT_PWD
# Create databases and users:
for db in ${MYSQL_DB_LIST[@]}
do
  echo "CREATE DATABASE $db CHARACTER SET utf8 COLLATE utf8_general_ci" | mysql -u root -p$MYSQL_ROOT_PWD
  mysql -uroot -p$MYSQL_ROOT_PWD -e "GRANT ALL PRIVILEGES ON $db.* TO $MYSQL_USER@localhost IDENTIFIED BY '$MYSQL_USER_PWD'"
done
# Perform config:
default_file /etc/mysql/my.cnf mysql/my.cnf
place_file   mysql/my.cnf /etc/mysql/my.cnf
# Cleanup:
rm -rf /var/lib/mysql/ib_log*
service mysql restart

# Install php:
for pkg in ${PHP_PACKAGES[@]}
do
  install_package $pkg
done
# Perform config:
mkdir $SYNC_DIR/$DEFAULT_DIR/php
default_file /etc/php5/cli/php.ini php/cli-php.ini
place_file   php/cli-php.ini /etc/php5/cli/php.ini
php5enmod mcrypt

# Install composer:
curl -s http://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

apt-get upgrade -y
apt-get dist-upgrade -y
apt-get autoremove -y
