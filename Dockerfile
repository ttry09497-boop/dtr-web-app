FROM php:8.2-apache

# Enable Apache mod_rewrite (for pretty URLs if you use them)
RUN a2enmod rewrite

# Copy all project files into Apache's web root
COPY . /var/www/html/

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 (Render will route to this)
EXPOSE 80
