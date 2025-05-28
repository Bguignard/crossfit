FROM php:8.4-fpm-alpine
ARG TIMEZONE="Europe/Paris"

COPY php.ini /usr/local/etc/php/conf.d/docker-php-config.ini



RUN apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        oniguruma-dev \
        postgresql-dev \
        linux-headers \
    && apk add --no-cache \
        git \
        shadow \
        postgresql-client \
        nodejs \
        npm \
    && pecl install \
        pcov \
        redis \
    && docker-php-ext-enable \
        pcov \
        redis \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install \
        exif \
        pdo \
        pdo_pgsql \
        intl \
        opcache \
    && apk del -f .build-deps



#RUN apt-get update && apt-get install -y \
#    gnupg \
#    g++ \
#    procps \
#    openssl \
#    git \
#    unzip \
#    zlib1g-dev \
#    libzip-dev \
#    libfreetype6-dev \
#    libpng-dev \
#    libjpeg-dev \
#    libicu-dev  \
#    libonig-dev \
#    libxslt1-dev \
#    acl \
#    xvfb \
#    wkhtmltopdf \
#    make \
#    && echo 'alias sf="php bin/console"' >> ~/.bashrc

# Install Postgre PDO
#RUN set -ex \
#    && apk --no-cache add \
#    postgresql-dev

#RUN docker-php-ext-install pgsql
#
#RUN docker-php-ext-install \
#    pdo pdo_pgsql pdo_mysql zip xsl gd intl opcache exif mbstring sockets soap calendar xml

# Set timezone
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone \
    && printf '[PHP]\ndate.timezone = "%s"\n', ${TIMEZONE} > /usr/local/etc/php/conf.d/tzone.ini \
    && "date"

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/symfony