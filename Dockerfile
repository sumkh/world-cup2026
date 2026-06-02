FROM php:8.2-apache

# libpq-dev   → required by pdo_pgsql
# libcurl4-openssl-dev → required by the curl extension (used in cron.php)
RUN apt-get update \
 && apt-get install -y libpq-dev libcurl4-openssl-dev \
 && rm -rf /var/lib/apt/lists/*

# Install PHP extensions needed by the app
RUN docker-php-ext-install pdo pdo_pgsql curl

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy app source into Apache's web root
COPY . /var/www/html/

# Drop build/config files from the served directory
RUN rm -f /var/www/html/Dockerfile /var/www/html/render.yaml

# Fix ownership
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
