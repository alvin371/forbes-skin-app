FROM php:8.4-apache AS base

WORKDIR /var/www/html

# Install runtime and build dependencies once so later stages can reuse them.
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libmemcached-dev \
    zlib1g-dev \
    libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        gd \
        mysqli \
        pdo \
        pdo_mysql \
        zip \
        mbstring \
        exif \
        pcntl \
        bcmath \
        intl \
    && pecl install memcached \
    && docker-php-ext-enable memcached \
    && a2enmod rewrite headers expires deflate \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN echo "upload_max_filesize = 50M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "date.timezone = Asia/Jakarta" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "zend.exception_ignore_args = Off" >> /usr/local/etc/php/conf.d/custom.ini

RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html\n\
\n\
    <Directory /var/www/html>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
\n\
    SetEnvIf X-Forwarded-Proto "https" HTTPS=on\n\
\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>\n' > /etc/apache2/sites-available/000-default.conf

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
