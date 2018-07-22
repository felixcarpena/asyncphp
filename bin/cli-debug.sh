#!/bin/bash

DIR="$(pwd)"
CMD=$@

PHP_IDE_CONFIG="serverName=_" php -dxdebug.remote_host=172.21.0.1 -dxdebug.default_enable=1 -dxdebug.remote_autostart=1 -dxdebug.remote_enable=1 -dxdebug.remote_mode=req $DIR/$CMD
