#!/usr/bin/env bash

sed -i "/^$1=/s/=.*/=$2/" .env
