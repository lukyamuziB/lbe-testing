FROM laradock/php-fpm:7.0--1.2

# Install git
RUN apt-get update && apt-get install -y git

# Install MySql client
RUN apt-get install -y mysql-client

# Install Composer and make it available in the PATH
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

# Set the WORKDIR to /usr/src so all following commands run in /app
RUN mkdir -p /usr/php/src
COPY . /usr/php/src
WORKDIR /usr/php/src

# Install dependencies with Composer.
# --prefer-source fixes issues with download limits on Github.
# --no-interaction makes sure composer can run fully automated
RUN composer install --prefer-source --no-interaction