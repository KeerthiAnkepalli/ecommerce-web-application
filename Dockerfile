FROM php:8.1-fpm

# Install mysqli
RUN docker-php-ext-install mysqli

# Install nginx
RUN apt-get update && apt-get install -y nginx

# Copy project files
COPY . /var/www/html/

# Copy nginx config
COPY nginx.conf /etc/nginx/nginx.conf

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD service nginx start && php-fpm