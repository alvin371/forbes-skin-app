FROM php:8.2-apache

WORKDIR /var/www/html

# Install system dependencies
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

# Copy Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . /var/www/html

# Create .env from example if not exists
RUN if [ ! -f .env ]; then cp .env.example .env; fi

# Create necessary directories
RUN mkdir -p /var/www/html/application/cache/sessions \
    && mkdir -p /var/www/html/application/logs \
    && mkdir -p /var/www/html/assets/uploads

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/application/cache \
    && chmod -R 775 /var/www/html/application/logs \
    && chmod -R 775 /var/www/html/assets/uploads

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader || true

# PHP configuration
RUN echo "upload_max_filesize = 50M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "date.timezone = Asia/Jakarta" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "zend.exception_ignore_args = Off" >> /usr/local/etc/php/conf.d/custom.ini

# Apache virtual host configuration
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
    # Make Apache/PHP aware of HTTPS when behind reverse proxy\n\
    SetEnvIf X-Forwarded-Proto "https" HTTPS=on\n\
\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>\n' > /etc/apache2/sites-available/000-default.conf

EXPOSE 80

CMD ["apache2-foreground"]
