FROM php:8.3-apache

RUN docker-php-ext-install pdo pdo_mysql
RUN apt-get update && apt-get install -y libcurl4-openssl-dev \
    && docker-php-ext-install curl