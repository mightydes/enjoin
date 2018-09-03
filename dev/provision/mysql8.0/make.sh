#!/usr/bin/env bash

#
# Original guide:
# https://dev.mysql.com/doc/refman/8.0/en/linux-installation-yum-repo.html
#

yum -y install http://repo.mysql.com/mysql80-community-release-el7-1.noarch.rpm
yum repolist
yum -y install mysql-community-server
yellow "Configure MySQL"
systemctl start mysqld
default_file /etc/my.cnf mysql$MYSQL_VER/my.cnf
place_file   mysql$MYSQL_VER/my.cnf /etc/my.cnf
mkdir -p /var/log/mysql
chown -R mysql:mysql /var/log/mysql
systemctl restart mysqld
systemctl enable mysqld
MYSQL_TEMP_ROOT_PWD=$(perl -lne "print \$1 if /temporary password[^\:]+\:\s*([^\n]+)/" /var/log/mysqld.log)
mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '$MYSQL_ROOT_PWD'" --user=root --password=$MYSQL_TEMP_ROOT_PWD --connect-expired-password
yellow "Create databases and users"
for db in ${SQL_DB_LIST[@]}
do
    mysql -e "CREATE DATABASE IF NOT EXISTS $db CHARACTER SET utf8 COLLATE utf8_general_ci" -uroot -p$MYSQL_ROOT_PWD
#    mysql -e "CREATE USER IF NOT EXISTS '$SQL_USER'@'localhost' IDENTIFIED BY '$SQL_USER_PWD'" -uroot -p$MYSQL_ROOT_PWD
    mysql -e "CREATE USER IF NOT EXISTS '$SQL_USER'@'localhost' IDENTIFIED WITH mysql_native_password BY '$SQL_USER_PWD'" -uroot -p$MYSQL_ROOT_PWD
    mysql -e "GRANT ALL ON $db.* TO '$SQL_USER'@'localhost'" -uroot -p$MYSQL_ROOT_PWD
done
