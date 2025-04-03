FROM ghcr.io/rollun-lc/rollun-ops/base-images/php-fpm-8.1

# Install ping and ip command for entrypoint.sh
RUN apt-get update \
    && apt-get install -y iputils-ping \
    iproute2

# Install xdebug
RUN pecl install xdebug-3.4.2 && docker-php-ext-enable xdebug

COPY ./php-fpm.conf  /usr/local/etc/php-fpm.conf
COPY ./conf.d  /usr/local/etc/php/conf.d

# GID and UID mapping for ROOT user
ARG WWW_DATA_UID=33
ARG WWW_DATA_GID=33

# Change www-data UID and GID to match host
RUN usermod -u $WWW_DATA_UID www-data && groupmod -g $WWW_DATA_GID www-data

# Allow www-data to switch to root without a password
RUN echo "www-data ALL=(ALL) NOPASSWD: ALL" >> /etc/sudoers

## Set entrypoint
#COPY ./entrypoint.sh /usr/local/bin/docker-php-entrypoint
#RUN chmod +x /usr/local/bin/docker-php-entrypoint

USER www-data
WORKDIR /var/www/app

CMD ["php-fpm", "-R"]