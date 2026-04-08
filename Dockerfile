FROM php:8.2-fpm-alpine

RUN apk add --no-cache nginx

COPY . /var/www/html/
RUN chmod 755 /var/www/html/index.php

RUN echo 'server {
    listen PORT_PLACEHOLDER;
    root /var/www/html;
    index index.php;
    location / { try_files $uri $uri/ /index.php?$args; }
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}' > /etc/nginx/http.d/default.conf

COPY start.sh /start.sh
RUN chmod +x /start.sh

CMD ["/start.sh"]
