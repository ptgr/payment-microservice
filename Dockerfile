FROM mlocati/php-extension-installer:2
FROM composer/composer:2-bin
FROM php:8.2-fpm-alpine AS php_dev

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_ENV=dev XDEBUG_MODE=off
ENV PATH="${PATH}:/root/.composer/vendor/bin"

WORKDIR /srv/app
RUN apk add --no-cache acl fcgi file gettext

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN set -eux; install-php-extensions xdebug-3.2.2 pdo_pgsql

COPY --link docker/php/conf.d/app.dev.ini $PHP_INI_DIR/conf.d/

COPY --link docker/php/php-fpm.d/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf
RUN mkdir -p /var/run/php

COPY --link docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

COPY --from=composer/composer:2-bin --link /composer /usr/bin/composer

VOLUME /srv/app/var/

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]