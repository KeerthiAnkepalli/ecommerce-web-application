FROM php:8.1-apache

# Remove all MPM modules
RUN rm -f /etc/apache2/mods-enabled/mpm_* \
    && rm -f /etc/apache2/mods-available/mpm_*

# Copy project files
COPY . /var/www/html/

# Install mysqli
RUN docker-php-ext-install mysqli

# Permissions
RUN chown -R www-data:www-data /var/www/html

# Force Apache to use prefork
RUN echo "LoadModule mpm_prefork_module /usr/lib/apache2/modules/mod_mpm_prefork.so" \
    > /etc/apache2/mods-enabled/mpm_prefork.load

EXPOSE 80

CMD ["apache2-foreground"]