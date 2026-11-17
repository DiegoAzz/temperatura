FROM php:8.5-apache

RUN docker-php-ext-install pdo pdo_mysql

COPY temperatura/ /var/www/html/temperatura/
COPY temperatura_app/ /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80
