FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    curl  \
    libzip-dev \
    nano \
    unzip \
    wget \
    zip

RUN docker-php-ext-install pdo pdo_mysql

RUN pecl install redis && docker-php-ext-enable redis

RUN apt-get install -y libicu-dev \
&& docker-php-ext-configure intl \
&& docker-php-ext-install intl

RUN wget https://getcomposer.org/installer -O - -q | php -- --install-dir=/bin --filename=composer --quiet

WORKDIR /var/www
