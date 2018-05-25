#!/usr/bin/env bash

yum install -y postgresql-server postgresql-contrib
postgresql-setup initdb
mkdir -p /var/log/postgresql
default_file /var/lib/pgsql/data/postgresql.conf postgresql$PGSQL_VER/postgresql.conf
place_file   postgresql$PGSQL_VER/postgresql.conf /var/lib/pgsql/data/postgresql.conf
default_file /var/lib/pgsql/data/pg_hba.conf postgresql$PGSQL_VER/pg_hba.conf
place_file   postgresql$PGSQL_VER/pg_hba.conf /var/lib/pgsql/data/pg_hba.conf
chown -R postgres:postgres /var/log/postgresql
systemctl start postgresql
systemctl enable postgresql
sudo -u postgres createuser $SQL_USER
sudo -u postgres psql -c "ALTER USER $SQL_USER WITH ENCRYPTED PASSWORD '$SQL_USER_PWD'"
for db in ${SQL_DB_LIST[@]}
do
    sudo -u postgres createdb $db --owner=$SQL_USER
done
