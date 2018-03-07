#!/usr/bin/env bash

DEBIAN_FRONTEND=noninteractive apt-get install -y -q mysql-server
mysqladmin -u root password $MYSQL_ROOT_PWD
yellow "Create databases and users"
for db in ${MYSQL_DB_LIST[@]}
do
    mysql -e "CREATE DATABASE $db CHARACTER SET utf8 COLLATE utf8_general_ci" -uroot -p$MYSQL_ROOT_PWD
    mysql -e "GRANT ALL PRIVILEGES ON $db.* TO $MYSQL_USER@localhost IDENTIFIED BY '$MYSQL_USER_PWD'" -uroot -p$MYSQL_ROOT_PWD
done
yellow "Configure MySQL"
default_file /etc/mysql/my.cnf "mysql$MYSQL_VER/my.cnf"
place_file   "mysql$MYSQL_VER/my.cnf" /etc/mysql/my.cnf
rm -rf /var/lib/mysql/ib_log*
service mysql restart
