FROM oven/bun AS builder
WORKDIR /app
COPY . /app/
RUN ls -al /app
RUN bun install --frozen-lockfile
RUN bun run build

FROM composer AS phpbuilder
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --ignore-platform-req=ext-frankenphp --ignore-platform-req=ext-pgsql

FROM dunglas/frankenphp
RUN install-php-extensions \
	pdo_pgsql \
	pgsql \
	gd \
	intl \
	zip \
	opcache
WORKDIR /app
COPY --from=builder /app/build /app/build
COPY --from=phpbuilder /app/vendor /app/vendor
COPY templates /app/templates
COPY Caddyfile.prod /etc/caddy/Caddyfile
COPY src /app/src

COPY config.php /app/config.php

EXPOSE 80
EXPOSE 443/udp
EXPOSE 443/tcp



