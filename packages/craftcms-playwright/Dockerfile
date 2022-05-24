FROM composer as create

RUN composer create-project -n --ignore-platform-reqs craftcms/craft .
RUN sed -i "s/];/\'requireMatchingUserAgentForSession\' => false,\n];/g" config/general.php
RUN rm -f .env

FROM node:16-alpine as nodejs

COPY ./package.json ./package.json
COPY ./package-lock.json ./package-lock.json
RUN npm ci

FROM craftcms/base:8.0 AS craft

RUN curl -fsSL https://deb.nodesource.com/setup_16.x | bash -

RUN apt-get update -y

RUN apt install -y nodejs mysql-client

RUN apt-get -y install libglib2.0-0 libnss3 libnspr4 libatk1.0-0 libatk-bridge2.0-0 libcups2 libdrm2 libdbus-1-3 libxkbcommon0 libxcomposite1 libxdamage1 libxext6 libxfixes3 libxrandr2 libgbm1 libpango-1.0-0 libcairo2 libasound2 libatspi2.0-0

RUN curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php

RUN php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer

COPY --from=create --chown=appuser /app /app

COPY --chown=appuser ./. /app/repos/repo

COPY --from=nodejs --chown=appuser /node_modules /app/repos/repo/node_modules

RUN cp -rvfp /app/repos/repo/node_modules/@craftcms/playwright/init.sh /usr/local/bin/.