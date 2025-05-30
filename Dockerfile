FROM oven/bun AS builder
WORKDIR /app
COPY package.json vite.config.ts bun.lock tsconfig.json /app/
COPY assets /app
COPY public /app
RUN bun install --frozen-lockfile
RUN bun run build

FROM composer AS phpbuilder
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --ignore-platform-req=ext-frankenphp

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

EXPOSE 80
EXPOSE 443/udp
EXPOSE 443/tcp



