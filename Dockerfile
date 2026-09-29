FROM php:8.2-apache

# Enable Apache's mod_rewrite for our .htaccess routing
RUN a2enmod rewrite

# Install the MySQL PDO extension so our database connection works
RUN docker-php-ext-install pdo pdo_mysql

# Set the working directory
WORKDIR /var/www/html

# We copy the code into a subfolder named "aurahub" 
# This ensures that all of your hardcoded links (like /aurahub/public/watch) 
# will continue to work perfectly on the live server without needing to rewrite them!
COPY . /var/www/html/aurahub/

# Make sure permissions are correct for Apache
RUN chown -R www-data:www-data /var/www/html/aurahub

# Expose port 80 for Render's routing
EXPOSE 80

# Add a quick redirect at the root of the server
# So when Render pings the base URL or users visit it, they get forwarded to your app!
RUN echo "<?php header('Location: /aurahub/'); exit; ?>" > /var/www/html/index.php
