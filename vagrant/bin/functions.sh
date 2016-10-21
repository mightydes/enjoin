#!/usr/bin/env bash

install_package() {
    apt-get install -y $1
}

silent_install_package() {
    DEBIAN_FRONTEND=noninteractive apt-get install -y -q $1
}

default_file() {
    dirname $SYNC_DIR/$DEFAULT_DIR/$2 | xargs mkdir -p
    cp $1 $SYNC_DIR/$DEFAULT_DIR/$2
}

place_file() {
    cp -aR $SYNC_DIR/$PLACE_DIR/$1 $2
}

update_locales() {
    ln -sf /usr/share/zoneinfo/Europe/Moscow /etc/localtime
    locale-gen en_US en_US.UTF-8 ru_RU ru_RU.UTF-8
    dpkg-reconfigure locales
    update-locale LANG=ru_RU.UTF-8
}
