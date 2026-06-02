FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite (needed if .htaccess rules are added later)
RUN a2enmod rewrite

# Copy app source into Apache's web root
COPY . /var/www/html/

# Drop the Dockerfile and Render config from the served directory
RUN rm -f /var/www/html/Dockerfile /var/www/html/render.yaml

# Fix ownership
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
