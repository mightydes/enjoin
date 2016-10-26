#!/usr/bin/env bash

bin/set-env.sh ENJ_DIALECT postgresql
psql -c "CREATE DATABASE enjoin_test;" -U postgres
psql -c "CREATE USER enjoin_test WITH password 'enjoin_test';" -U postgres
psql -c "GRANT ALL privileges ON DATABASE enjoin_test TO enjoin_test;" -U postgres
