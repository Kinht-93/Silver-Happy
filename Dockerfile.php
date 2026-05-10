FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql mysqli opcache && \
    a2enmod rewrite proxy proxy_http

COPY apache-app.conf /etc/apache2/conf-enabled/app.conf

EXPOSE 80