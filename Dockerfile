FROM php:8.2-apache

RUN apt-get update && \
    apt-get install -y libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_sqlite sqlite3
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html/
EXPOSE 80
