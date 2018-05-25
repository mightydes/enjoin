#!/usr/bin/env bash

yum -y install http://www.percona.com/downloads/percona-release/redhat/0.1-4/percona-release-0.1-4.noarch.rpm
yum repolist
yum -y install Percona-Server-server-55
systemctl start mysqld
mysql -e "UPDATE mysql.user SET Password = PASSWORD('$MYSQL_ROOT_PWD') WHERE User = 'root'"
mysql -e "DROP USER ''@'localhost'"
mysql -e "DROP USER ''@'$(hostname)'"
mysql -e "DROP DATABASE test"
yellow "Percona UDF (User Defined Function)"
mysql -e "CREATE FUNCTION fnv1a_64 RETURNS INTEGER SONAME 'libfnv1a_udf.so'"
mysql -e "CREATE FUNCTION fnv_64 RETURNS INTEGER SONAME 'libfnv_udf.so'"
mysql -e "CREATE FUNCTION murmur_hash RETURNS INTEGER SONAME 'libmurmur_udf.so'"
mysql -e "FLUSH PRIVILEGES"
systemctl enable mysqld
yellow "Create databases and users"
for db in ${SQL_DB_LIST[@]}
do
    mysql -e "CREATE DATABASE IF NOT EXISTS $db CHARACTER SET utf8 COLLATE utf8_general_ci" -uroot -p$MYSQL_ROOT_PWD
    mysql -e "GRANT ALL PRIVILEGES ON $db.* TO $SQL_USER@localhost IDENTIFIED BY '$SQL_USER_PWD'" -uroot -p$MYSQL_ROOT_PWD
done
yellow "Configure MySQL"
default_file /etc/my.cnf percona$PERCONA_VER/my.cnf
place_file   percona$PERCONA_VER/my.cnf /etc/my.cnf
rm -rf /var/lib/mysql/ib_log*
mkdir -p /var/log/mysql
chown -R mysql:mysql /var/log/mysql
systemctl restart mysqld
