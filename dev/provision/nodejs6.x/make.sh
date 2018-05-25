#!/usr/bin/env bash

curl -sL https://rpm.nodesource.com/setup_6.x | bash -
yum -y install nodejs
npm i -g --silent --no-progress gulp
