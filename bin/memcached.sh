#!/usr/bin/env bash

bin/set-env.sh ENJ_CACHE memcached
echo "extension = memcached.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
