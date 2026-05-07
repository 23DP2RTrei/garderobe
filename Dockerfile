FROM php:8.2-apache

RUN a2enmod rewrite
RUN docker-php-ext-install pdo pdo_mysql

COPY . /var/www/html/

RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && echo '<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>' \
       >> /etc/apache2/apache2.conf

EXPOSE 80
