FROM php:8.1-apache

# Remove all existing MPM configs (hard fix)
RUN rm -f /etc/apache2/mods-enabled/mpm_* || true

# Enable ONLY prefork (required for PHP)
RUN a2enmod mpm_prefork rewrite

# Install mysqli (required for your project)
RUN docker-php-ext-install mysqli

# Copy project files
COPY . /var/www/html/

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

# Start Apache properly
CMD ["apache2-foreground"]