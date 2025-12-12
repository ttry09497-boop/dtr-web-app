FROM php:8.2-apache

ENV DEBIAN_FRONTEND=noninteractive

# Install system deps and PostgreSQL driver, then clean up apt lists to keep image small
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      libpq-dev \
      unzip \
      git \
      zlib1g-dev \
      libzip-dev \
 && docker-php-ext-install pdo pdo_pgsql pgsql zip \
 && a2enmod rewrite \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html/

# Set correct file permissions
RUN chown -R www-data:www-data /var/www/html \
 && chmod -R 755 /var/www/html

EXPOSE 80
