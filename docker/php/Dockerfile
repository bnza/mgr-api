#syntax=docker/dockerfile:1.4

# Adapted from https://github.com/dunglas/symfony-docker

# The different stages of this Dockerfile are meant to be built into separate images
# https://docs.docker.com/develop/develop-images/multistage-build/#stop-at-a-specific-build-stage
# https://docs.docker.com/compose/compose-file/#target


# Prod image
FROM php:8.4-fpm-alpine AS app_php

ENV APP_ENV=prod

WORKDIR /srv/api

# php extensions installer: https://github.com/mlocati/docker-php-extension-installer
COPY --from=mlocati/php-extension-installer:latest --link /usr/bin/install-php-extensions /usr/local/bin/

# persistent / runtime deps
# hadolint ignore=DL3018
RUN apk update \
    && apk add --no-cache \
		acl \
    	autoconf \
		fcgi \
		file \
		gettext \
    	gcc \
		git \
    	ghostscript \
    	imagemagick-dev \
    	redis \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS imagemagick-dev \
#	&& pecl install imagick \
#    && docker-php-ext-enable imagick \
    && rm -fr /tmp/pear \
    && apk del .build-deps


RUN set -eux; \
    install-php-extensions \
		apcu \
    	bcmath \
    	gd \
		intl \
        Imagick/imagick@65e27f2bc0 \
		opcache \
    	redis \
		zip \
    ;

###> recipes ###
###> doctrine/doctrine-bundle ###
RUN set -eux; \
    install-php-extensions pdo_pgsql
###< doctrine/doctrine-bundle ###
###< recipes ###


#RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS imagemagick-dev \
#&& pecl install imagick \
#&& docker-php-ext-enable imagick \
#&& apk del .build-deps

#RUN pecl install imagick && \
#    docker-php-ext-enable imagick

RUN sed -i '/^<\/policymap>/i <policy domain="coder" rights="read | write" pattern="PDF" \/>' /etc/ImageMagick-7/policy.xml

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY --link docker/php/conf.d/app.ini $PHP_INI_DIR/conf.d/
COPY --link docker/php/conf.d/app.prod.ini $PHP_INI_DIR/conf.d/

COPY --link docker/php/php-fpm.d/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf
RUN mkdir -p /var/run/php

COPY --link docker/php/docker-healthcheck.sh /usr/local/bin/docker-healthcheck
RUN chmod +x /usr/local/bin/docker-healthcheck

HEALTHCHECK --interval=10s --timeout=3s --retries=3 CMD ["docker-healthcheck"]

COPY --link docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

COPY --from=composer/composer:2-bin --link /composer /usr/bin/composer

# prevent the reinstallation of vendors at every changes in the source code
COPY --link api/composer.* api/symfony.* ./
# copy sources
COPY --link api ./
RUN rm -Rf docker/

RUN set -eux; \
	composer install --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress; \
	composer clear-cache

RUN set -eux; \
	mkdir -p var/cache var/log; \
	composer dump-autoload --classmap-authoritative --no-dev; \
	composer dump-env prod; \
	composer run-script --no-dev post-install-cmd; \
	chmod +x bin/console; sync;

# Dev image
FROM app_php AS app_php_dev

ENV APP_ENV=dev XDEBUG_MODE=off
VOLUME /srv/api/var/

RUN rm "$PHP_INI_DIR/conf.d/app.prod.ini"; \
	mv "$PHP_INI_DIR/php.ini" "$PHP_INI_DIR/php.ini-production"; \
	mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY --link docker/php/conf.d/app.dev.ini $PHP_INI_DIR/conf.d/

RUN set -eux; \
	install-php-extensions \
    	xdebug \
    ;

RUN rm -f .env.local.php
