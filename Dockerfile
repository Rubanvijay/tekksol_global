# Use the official PHP-Apache image
FROM php:8.2-apache

# Copy project files to the web root
COPY . /var/www/html/

# Install mysqli extension for MySQL connection
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Expose port 80
EXPOSE 80
