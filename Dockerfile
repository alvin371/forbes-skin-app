# Heavy base (apt + compiled PHP extensions + composer + php.ini/vhost) is prebuilt
# and pushed as gilangp/forbes-base by .github/workflows/base.yml. See Dockerfile.base.
# Override the tag with --build-arg BASE_IMAGE=... if needed.
ARG BASE_IMAGE=gilangp/forbes-base:latest
FROM ${BASE_IMAGE} AS base

WORKDIR /var/www/html

FROM base AS vendor-prod

COPY composer.json composer.lock /var/www/html/

RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

FROM base AS vendor-dev

COPY composer.json composer.lock /var/www/html/

RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    composer install \
    --no-interaction \
    --no-progress \
    --prefer-dist

FROM base AS ci

COPY . /var/www/html
COPY --from=vendor-dev /var/www/html/vendor /var/www/html/vendor

RUN if [ ! -f .env ] && [ -f .env.example ]; then cp .env.example .env; fi \
    && mkdir -p /var/www/html/application/cache/sessions \
    && mkdir -p /var/www/html/application/logs \
    && mkdir -p /var/www/html/assets/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/application/cache \
    && chmod -R 775 /var/www/html/application/logs \
    && chmod -R 775 /var/www/html/assets/uploads

FROM base AS runtime

COPY . /var/www/html
COPY --from=vendor-prod /var/www/html/vendor /var/www/html/vendor

# The runtime image defaults to production: db_debug is off, so the app boots (and /healthz
# answers 200) even with no database reachable — e.g. the CI smoke test runs the bare image
# with no DB. Real deployments mount their own .env (with real DB creds) over this baked one.
RUN if [ ! -f .env ] && [ -f .env.example ]; then cp .env.example .env; fi \
    && sed -i 's/^CI_ENV=.*/CI_ENV=production/' .env \
    && mkdir -p /var/www/html/application/cache/sessions \
    && mkdir -p /var/www/html/application/logs \
    && mkdir -p /var/www/html/assets/uploads \
    && rm -rf /var/www/html/tests /var/www/html/tools \
    && rm -f /var/www/html/phpunit.xml /var/www/html/.php-cs-fixer.dist.php \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/application/cache \
    && chmod -R 775 /var/www/html/application/logs \
    && chmod -R 775 /var/www/html/assets/uploads

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 CMD php -r '$body = @file_get_contents("http://127.0.0.1/healthz"); if ($body === false) { exit(1); } $payload = json_decode($body, true); exit((is_array($payload) && !empty($payload["ok"])) ? 0 : 1);'

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
