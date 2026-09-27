FROM php:8.3-apache
WORKDIR /var/www/html
COPY public/ /var/www/html/
RUN mkdir -p /var/www/data && chown -R www-data:www-data /var/www/data
EXPOSE 80
