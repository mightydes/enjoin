#!/usr/bin/env bash

debconf-set-selections <<< "mysql-server-5.6 mysql-server/root_password password $MYSQL_ROOT_PWD"
debconf-set-selections <<< "mysql-server-5.6 mysql-server/root_password_again password $MYSQL_ROOT_PWD"
DEBIAN_FRONTEND=noninteractive apt-get install -y mysql-server-5.6
yellow "Create databases and users"
for db in ${MYSQL_DB_LIST[@]}
do
    mysql -e "CREATE DATABASE $db CHARACTER SET utf8 COLLATE utf8_general_ci" -uroot -p$MYSQL_ROOT_PWD
    mysql -e "GRANT ALL PRIVILEGES ON $db.* TO $MYSQL_USER@localhost IDENTIFIED BY '$MYSQL_USER_PWD'" -uroot -p$MYSQL_ROOT_PWD
done
yellow "Configure MySQL"
default_file /etc/mysql/my.cnf "mysql$MYSQL_VER/my.cnf"
service mysql restart
