# Single dev image used by both the `app` (PHP) and `vite` (Node) services.
# PHP 8.5 CLI with Composer and Node 24 copied in from their official images,
# so nothing has to be installed on the host.
FROM php:8.5-cli-bookworm

ARG UID=1000
ARG GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip sqlite3 \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install -j"$(nproc)" pcntl bcmath

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Node 24 (same Debian base, so the binary is ABI compatible)
COPY --from=node:24-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# Non-root user so bind-mounted files are not root-owned on Linux hosts.
RUN groupadd -g "${GID}" app \
    && useradd -m -u "${UID}" -g app -s /bin/bash app

ENV COMPOSER_HOME=/home/app/.composer \
    COMPOSER_ALLOW_SUPERUSER=1 \
    NPM_CONFIG_UPDATE_NOTIFIER=false

WORKDIR /app
USER app
