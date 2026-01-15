FROM php:8.1-apache

# Disable other MPMs, enable prefork (PHP compatible)
RUN a2dismod mpm_event mpm_worker || true \
    && a2enmod mpm_prefork rewrite

# Install mysqli extension (THIS IS THE KEY FIX)
RUN docker-php-ext-install mysqli

# Copy project files
COPY . /var/www/html/

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

# Keep Apache running
CMD ["apache2-foreground"]