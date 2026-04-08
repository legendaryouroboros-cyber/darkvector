#!/bin/sh
sed -i "s/PORT_PLACEHOLDER/$PORT/g" /etc/nginx/http.d/default.conf
php-fpm -D
sleep 2
nginx -g "daemon off;"
