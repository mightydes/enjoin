#!/usr/bin/env bash

bin/set-env.sh ENJ_DIALECT mysql
mysql -e "CREATE DATABASE enjoin_test;"
mysql -u root -e "GRANT ALL PRIVILEGES ON enjoin_test.* TO enjoin_test@localhost IDENTIFIED BY 'enjoin_test';"
