FROM maxrollundev/php-fpm-8.0

# Install xdebug
RUN pecl install xdebug-3.4.2 && docker-php-ext-enable xdebug

# Script to wait a port
COPY ./wait-for-it.sh /usr/local/bin/wait-for-it
RUN chmod 555 /usr/local/bin/wait-for-it

# php configs
COPY ./php-fpm.conf  /usr/local/etc/php-fpm.conf
COPY ./conf.d  /usr/local/etc/php/conf.d

# COPY preserves source modes, so a context checked out with a group-shared umask
# (0660) leaves these unreadable for www-data and php-fpm dies with exit 78.
RUN chmod 644 /usr/local/etc/php-fpm.conf && chmod -R a+r /usr/local/etc/php/conf.d

# GID and UID mapping for www-data user
ARG WWW_DATA_UID=33
ARG WWW_DATA_GID=33

RUN usermod -u $WWW_DATA_UID www-data && groupmod -g $WWW_DATA_GID www-data

USER www-data
WORKDIR /var/www/app

CMD ["php-fpm", "-R"]