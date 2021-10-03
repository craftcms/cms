FROM mcr.microsoft.com/vscode/devcontainers/php:8
RUN apt-get update
RUN apt-get install -y libzip-dev mariadb-client
RUN docker-php-ext-install zip mysqli pdo pdo_mysql