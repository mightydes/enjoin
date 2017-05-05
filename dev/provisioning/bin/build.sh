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

yellow "Install MySQL"
DEBIAN_FRONTEND=noninteractive apt-get install -y -q mysql-server
mysqladmin -u root password $MYSQL_ROOT_PWD
yellow "Create databases and users"
for db in ${MYSQL_DB_LIST[@]}
do
    mysql -e "CREATE DATABASE $db CHARACTER SET utf8 COLLATE utf8_general_ci" -uroot -p$MYSQL_ROOT_PWD
    mysql -e "GRANT ALL PRIVILEGES ON $db.* TO $MYSQL_USER@localhost IDENTIFIED BY '$MYSQL_USER_PWD'" -uroot -p$MYSQL_ROOT_PWD
done
yellow "Configure MySQL"
default_file /etc/mysql/my.cnf mysql/my.cnf
place_file   mysql/my.cnf /etc/mysql/my.cnf
rm -rf /var/lib/mysql/ib_log*
service mysql restart

yellow "PHP7 friends"
apt-get install -y ${PHP7_PACKAGES[@]}
yellow "Configure PHP"
default_file /etc/php/7.0/cli/php.ini php7/cli-php.ini

yellow "Install Composer"
curl -s http://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

yellow "After install"
apt-get upgrade -y
apt-get autoremove -y
apt-get clean

yellow "Build app dependencies"
cd $USER_DIR
vagrant "composer install"
vagrant "npm i --silent --no-progress"

fi

exit 0
