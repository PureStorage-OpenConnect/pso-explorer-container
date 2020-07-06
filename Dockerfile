FROM php:7.4.7-fpm

# Copy composer.lock and composer.json
COPY composer.lock composer.json /var/www/

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libicu-dev \
    locales \
    libzip-dev \
    libyaml-dev \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    nginx

RUN pecl install redis yaml

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# PHP_CPPFLAGS are used by the docker-php-ext-* scripts
#ENV PHP_CPPFLAGS="$PHP_CPPFLAGS -std=c++11"

# Install extensions
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl \
    && docker-php-ext-install opcache \
    && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
    && docker-php-ext-install gd \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && docker-php-ext-enable redis \
    && docker-php-ext-enable yaml

RUN { \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=4000'; \
        echo 'opcache.revalidate_freq=2'; \
        echo 'opcache.fast_shutdown=1'; \
        echo 'opcache.enable_cli=1'; \
    } > /usr/local/etc/php/conf.d/php-opocache-cfg.ini

COPY nginx-site.conf /etc/nginx/sites-enabled/default
COPY entrypoint.sh /etc/entrypoint.sh
RUN chmod +x /etc/entrypoint.sh

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy existing application directory permissions
COPY . /var/www

RUN cd /var/www && php artisan key:generate
RUN cd /var/www && php artisan config:cache
RUN rm /var/www/storage/logs/laravel.log
RUN touch /var/www/storage/logs/laravel.log
RUN chown -R www-data:www-data /var/www

WORKDIR /var/www/

EXPOSE 80 443

ENTRYPOINT ["/etc/entrypoint.sh"]
