#!/usr/bin/env bash

apt-get install -y postgresql postgresql-contrib
sudo -u postgres createuser $SQL_USER
sudo -u postgres psql -c "ALTER USER $SQL_USER WITH ENCRYPTED PASSWORD '$SQL_USER_PWD'"
for db in ${SQL_DB_LIST[@]}
do
    sudo -u postgres createdb $db
    sudo -u postgres psql -c "ALTER DATABASE $db OWNER TO $SQL_USER"
done
