FROM php:8.2-cli

COPY . /app

WORKDIR /app

CMD php -S 0.0.0.0:${PORT:-8080} index.php
