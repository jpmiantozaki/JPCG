FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY public/ /var/www/html/

RUN mkdir -p /var/data \
    && chown -R www-data:www-data /var/data

EXPOSE 80