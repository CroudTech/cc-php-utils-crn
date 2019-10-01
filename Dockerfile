FROM php:7.3-fpm

# Installing dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libzip-dev \
    locales \
    git \
    zip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Installing extensions
RUN docker-php-ext-install mbstring zip exif pcntl bcmath

# Installing composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


# Changing Workdir
WORKDIR /src

# Copy contents
COPY ./src ./
COPY ./tests ./tests
COPY ./composer.* ./
COPY ./phpunit.xml ./phpunit.xml

# run composer
RUN composer install  --no-scripts --no-autoloader