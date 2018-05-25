#!/usr/bin/env bash

# Go to script directory:
BASE_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd $BASE_DIR
localedef -i ru_RU -f UTF-8 ru_RU
yum -y install perl # required for `get_env()` method...

# Import tools:
source functions.sh
source config.sh

DEBUG=false
if [ "$DEBUG" = false ] ; then

yellow "Before install"
hostnamectl set-hostname "$FQDN"
localectl set-locale LANG=ru_RU.utf8
timedatectl set-timezone Europe/Moscow
yellow "Disable firewall"
systemctl disable firewalld
systemctl stop firewalld
yellow "Disable selinux"
setenforce 0
sed -i 's/SELINUX=\(enforcing\|permissive\)/SELINUX=disabled/g' /etc/selinux/config
yellow "Enable ssh PasswordAuthentication"
sed -i 's/PasswordAuthentication no/PasswordAuthentication yes/g' /etc/ssh/sshd_config
systemctl restart sshd

yellow "Add rpm's"
yum -y install epel-release
yum -y install https://centos7.iuscommunity.org/ius-release.rpm
yum repolist

yellow "Create user directory via link"
rm -rf $USER_DIR
ln -fs $SYNC_DIR $USER_DIR
chown -h vagrant:vagrant $USER_DIR

yellow "Install packages"
yum -y group install "Development Tools"
yum -y install ${PACKAGES[@]}
yum -y autoremove

yellow "Install NodeJS $NODEJS_VER"
source "../nodejs$NODEJS_VER/make.sh"

yellow "Install Redis $REDIS_VER"
source "../redis$REDIS_VER/make.sh"

yellow "Install MySQL Percona Server $PERCONA_VER"
source "../percona$PERCONA_VER/make.sh"

yellow "Install PostgreSQL Server $PGSQL_VER"
source "../postgresql$PGSQL_VER/make.sh"

yellow "Install PHP $PHP_VER"
source "../php$PHP_VER/make.sh"

yellow "Install PHP Composer"
curl -s http://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

yellow "Build app dependencies"
cd $USER_DIR
vagrant "composer install"
vagrant "npm i --silent --no-progress"

yellow "After install"
yum -y autoremove
yum clean all
rm -rf /var/cache/yum

fi

exit 0
