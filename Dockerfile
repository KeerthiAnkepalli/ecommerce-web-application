FROM php:8.1-apache

# Disable ALL MPM modules explicitly
RUN a2dismod mpm_event mpm_worker mpm_prefork || true

# Enable ONLY prefork (required for PHP)
RUN a2enmod mpm_prefork rewrite

# Install mysqli extension
RUN docker-php-ext-install mysqli

# Copy project files
COPY . /var/www/html/

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

# Keep Apache running
CMD ["apache2-foreground"]