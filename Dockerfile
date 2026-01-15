FROM php:8.1-apache

# Disable all MPM modules first
RUN a2dismod mpm_event mpm_worker || true

# Enable prefork (required for PHP)
RUN a2enmod mpm_prefork rewrite

# Copy project files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

# Keep Apache running
CMD ["apache2-foreground"]