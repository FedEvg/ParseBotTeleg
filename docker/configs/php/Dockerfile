FROM php:8.3-fpm

# Оновлення пакетів та встановлення необхідних залежностей
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    vim \
    libzip-dev \
    libpcre3-dev \
    autoconf \
    gcc \
    make \
    pkg-config \
    && docker-php-ext-install pdo pdo_mysql

# Встановлення Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Встановлення Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Встановлення робочої директорії
WORKDIR /srv/src/telebot

# Копіювання конфігураційних файлів PHP
COPY docker/configs/php/conf.d/* $PHP_INI_DIR/conf.d/

# Вказівка команди для запуску програми
CMD [ "php-fpm" ]

