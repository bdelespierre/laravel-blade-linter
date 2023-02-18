FROM php:8.2-cli-alpine

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN apk add git \
    && rm -rf /var/cache/apk/* /var/tmp/* /tmp/*
WORKDIR /data
COPY composer.json composer.lock ./
RUN composer install --prefer-dist
COPY . .



FROM php:8.2-cli-alpine

COPY --from=0 /data /data
VOLUME ["/app"]
WORKDIR /app
ENV PATH /data/bin:$PATH
ENTRYPOINT ["blade-linter", "lint"]
