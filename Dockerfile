FROM php:8.1-apache

# Enable Apache rewrite
RUN a2enmod rewrite

# Set Apache Document Root
ENV APACHE_DOCUMENT_ROOT=/var/www/html

RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf

# Copy project files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80