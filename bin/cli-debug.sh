#!/bin/bash

DIR="$(pwd)"
CMD=$@
SERVER_NAME="_"
REMOTE_IP="172.21.0.1"

PHP_IDE_CONFIG="serverName=$SERVER_NAME" php -dxdebug.remote_host=$REMOTE_IP -dxdebug.default_enable=1 -dxdebug.remote_autostart=1 -dxdebug.remote_enable=1 -dxdebug.remote_mode=req $DIR/$CMD
