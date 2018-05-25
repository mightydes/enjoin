#!/usr/bin/env bash

yum -y --enablerepo=ius install redis32u
systemctl start redis
systemctl enable redis
default_file /etc/redis.conf redis$REDIS_VER/redis.conf
