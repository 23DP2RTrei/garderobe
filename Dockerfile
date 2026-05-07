FROM php:8.2-apache

RUN a2enmod rewrite
RUN docker-php-ext-install pdo pdo_mysql

COPY . /var/www/html/

RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && echo '<Directory /var/www/html>\nAllowOverride All\nRequire all granted\n</Directory>' \
        >> /etc/apache2/apache2.conf

# Atjauno ports.conf uz tīru stāvokli (kešu notīrīšanai)
RUN echo "Listen 80" > /etc/apache2/ports.conf

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
