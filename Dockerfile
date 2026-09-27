FROM php:8.3-apache
RUN docker-php-ext-install pdo_sqlite
WORKDIR /var/www/html
COPY public/ /var/www/html/
RUN mkdir -p /var/data && chown -R www-data:www-data /var/data
EXPOSE 80
