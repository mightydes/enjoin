#!/usr/bin/env bash

# Go to script directory:
BASE_DIR=$(dirname $0)
cd $BASE_DIR

# Fetch tools:
source config.sh
source functions.sh

DEBUG=false
if [ "$DEBUG" = false ] ; then

yellow "Update locales"
ln -sf /usr/share/zoneinfo/Europe/Moscow /etc/localtime
locale-gen en_US en_US.UTF-8 ru_RU ru_RU.UTF-8
dpkg-reconfigure locales
update-locale LANG=ru_RU.UTF-8

yellow "Upgrade dist"
apt-get update
apt-get upgrade -y --auto-remove

yellow "Add ppa's"
add-apt-repository -y ppa:chris-lea/redis-server
add-apt-repository -y ppa:ondrej/php
apt-get update

yellow "Create user directory via link"
rm -rf $USER_DIR
ln -fs $SYNC_DIR $USER_DIR
chown -h vagrant:vagrant $USER_DIR

yellow "Install packages"
apt-get install -y ${PACKAGES[@]}
apt-get autoremove -y

yellow "NodeJS friends"
apt-get purge -y node nodejs
curl -sL https://deb.nodesource.com/setup_6.x | bash -
apt-get install -y nodejs
npm i -g --silent --no-progress gulp node-gyp gnode

yellow "Install redis"
apt-get install -y redis-server
default_file /etc/redis/redis.conf redis/redis.conf

yellow "Install MySQL $MYSQL_VER"
source "../mysql$MYSQL_VER/make.sh"

yellow "Install PHP $PHP_VER"
source "../php$PHP_VER/make.sh"

yellow "Install Composer"
curl -s http://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

yellow "After install"
apt-get upgrade -y
apt-get dist-upgrade -y
apt-get autoremove -y
apt-get clean

yellow "Build app dependencies"
cd $USER_DIR
vagrant "composer install"
vagrant "npm i --silent --no-progress"

fi

exit 0
