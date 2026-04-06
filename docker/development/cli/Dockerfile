FROM php:8.4.19-cli-alpine

RUN apk add --no-cache autoconf build-base linux-headers \
    imagemagick imagemagick-dev \
    libjpeg-turbo libjpeg-turbo-dev \
    libpng libpng-dev \
    && pecl install xdebug-3.4.7 imagick \
    && docker-php-ext-enable xdebug imagick

RUN apk add --no-cache postgresql-dev git \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo_pgsql intl

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/bin --filename=composer --quiet

ENV COMPOSER_ALLOW_SUPERUSER 1
ENV COMPOSER_MEMORY_LIMIT -1

COPY ./conf.d $PHP_INI_DIR/conf.d/

RUN addgroup -g 1000 app && adduser -u 1000 -G app -s /bin/sh -D app

WORKDIR /app

USER app
