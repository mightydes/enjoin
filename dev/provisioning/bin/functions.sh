#!/usr/bin/env bash

# $1 color code
# $2 message to display
colorize() {
    local COLOR=$1
    local NC='\033[0m'
    printf "${COLOR}$2${NC}\n"
}

# $1 message to display
yellow() {
    colorize '\033[1;33m' "$1"
}

# $1 message to display
cyan() {
    colorize '\033[0;36m' "$1"
}

# $1 message to display
red() {
    colorize '\033[0;31m' "$1"
}

# $1 path to original file
# $2 path to copy
default_file() {
    dirname $DEFAULT_DIR/$2 | xargs mkdir -p
    cp $1 $DEFAULT_DIR/$2
}

# $1 path to place file
# $2 path to destination file
place_file() {
    cp -aR $PLACE_DIR/$1 $2
}

# Run command as `vagrant` user.
vagrant() {
    su - vagrant -c "cd $USER_DIR; $1"
}
