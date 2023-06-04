FROM php:8.2-cli

# Install PDO MySQL extension
RUN docker-php-ext-install pdo_mysql

COPY ./src/ /var/www/

WORKDIR /var/www