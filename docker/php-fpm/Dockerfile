FROM ghcr.io/rollun-lc/rollun-ops/base-images/php-fpm-8.1

# Install xdebug
RUN pecl install xdebug-3.4.2 && docker-php-ext-enable xdebug

# Default UID for www-data
ARG WWW_DATA_UID=33
ARG WWW_DATA_GID=33

# Change www-data UID and GID to match host
RUN usermod -u $WWW_DATA_UID www-data && groupmod -g $WWW_DATA_GID www-data

WORKDIR /var/www/app

CMD ["php-fpm"]