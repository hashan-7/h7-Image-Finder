# Use an official PHP image with Apache web server included
# Apache server eka sahitha niල PHP image ekak bawitha kirima
FROM php:8.2-apache

# Install necessary PHP extensions (GD for image resizing, cURL for API calls)
# Awashya PHP extensions (GD, cURL) install kirima
# Sodium is often needed by newer dependencies, exif for image metadata
RUN apt-get update && apt-get install -y \
    libfreetype-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd zip pdo pdo_mysql curl exif \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy project files into the Apache web root directory in the container
# Project files tika container eke web root ekata (/var/www/html/) copy kirima
COPY . /var/www/html/

# Optional: Set permissions if needed (usually not required for Apache user www-data)
# RUN chown -R www-data:www-data /var/www/html

# Optional: Enable Apache rewrite module if using .htaccess for routing (not needed for our index.php)
# RUN a2enmod rewrite

# Expose port 80 (Apache default port)
EXPOSE 80

# The base image (php:apache) already has a command to start Apache
# Apache start kirimata wishesha command ekak awashya nehe, base image eken eka siduwe
