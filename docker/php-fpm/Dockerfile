FROM maxrollundev/php-fpm-8.0

# Install xdebug
RUN pecl install xdebug-3.4.2 && docker-php-ext-enable xdebug

# Script to wait a port
COPY ./wait-for-it.sh /usr/local/bin/wait-for-it
RUN chmod 555 /usr/local/bin/wait-for-it

# php configs
COPY ./php-fpm.conf  /usr/local/etc/php-fpm.conf
COPY ./conf.d  /usr/local/etc/php/conf.d

# GID and UID mapping for www-data user
ARG WWW_DATA_UID=33
ARG WWW_DATA_GID=33

RUN usermod -u 1001 www-data && groupmod -g 118 www-data

USER www-data
WORKDIR /var/www/app

CMD ["php-fpm", "-R"]