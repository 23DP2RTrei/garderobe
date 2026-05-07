FROM php:8.2-cli

RUN docker-php-ext-install pdo pdo_mysql

COPY . /app/
WORKDIR /app

RUN mkdir -p /app/uploads && chmod 777 /app/uploads

CMD php -S 0.0.0.0:$PORT
