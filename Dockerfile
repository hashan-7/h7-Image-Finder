    # Simplified Dockerfile for testing deployment
    # Use the official PHP image with Apache
    FROM php:8.2-apache

    # Copy project files into the web root
    COPY . /var/www/html/

    # Expose port 80 (Apache default)
    EXPOSE 80

    # Base image handles starting Apache
    