FROM php:8.2-apache
RUN docker-php-ext-install pdo pdo_mysql mysqli opcache && a2enmod rewrite
RUN printf '<Directory /var/www/html>\nOptions Indexes FollowSymLinks\nAllowOverride All\nRequire all granted\n</Directory>\n' > /etc/apache2/conf-enabled/app.conf
EXPOSE 80
