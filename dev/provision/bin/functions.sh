#!/usr/bin/env bash

# $1 -- color code
# $2 -- message to display
colorize() {
    local COLOR=$1
    local NC='\033[0m'
    printf "${COLOR}$2${NC}\n"
}

# $1 -- message to display
yellow() {
    colorize '\033[1;33m' "$1"
}

# $1 -- message to display
cyan() {
    colorize '\033[0;36m' "$1"
}

# $1 -- message to display
red() {
    colorize '\033[0;31m' "$1"
}

# $1 -- path to original file
# $2 -- path to copy
default_file() {
    dirname $PROV_DIR/$2 | xargs mkdir -p
    cp $1 $PROV_DIR/$2.default
}

# $1 -- path to place file
# $2 -- path to destination file
place_file() {
    cat $PROV_DIR/$1 > $2
}

# Run command as `vagrant` user...
vagrant() {
    su - vagrant -c "cd $USER_DIR; $1"
}

# Example: echo `get_env MY_ENV_VAR`...
# $1 -- .env variable name
get_env() {
    perl -lne "print \$1 if /$1=([^\s]+)/" $SYNC_DIR/.env
}
