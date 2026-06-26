#!/bin/sh

PHP_BIN=/www/server/php/53/bin/php
SERVER_DIR=/www/wwwroot/kdjl/socketChat/server

pkill -9 -f "$SERVER_DIR/snb.php" >/dev/null 2>&1

cd "$SERVER_DIR" || exit 1
sleep 2

nohup "$PHP_BIN" "$SERVER_DIR/snb.php" 127.0.0.1 11211 1988 127.0.0.1 kdjl 0 >/dev/null 2>&1 &

exit 0