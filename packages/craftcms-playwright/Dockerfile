FROM ghcr.io/craftcms/image:8.2 AS craft

USER root

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash -

#RUN apt-get update -y

RUN apt install -y nodejs mysql-client git postgresql-client

RUN curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php

RUN php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer

# copy the files from the host to the container that we need
COPY ./craftcms/etc/nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./craftcms/etc/supervisord.d/nginx.ini /etc/supervisord.d/nginx.ini

# set the sockets and pid files to be writable by the appuser
RUN mkdir -p /var/log/nginx && chown -R appuser:appgroup /var/log/nginx
RUN chown -R appuser:appgroup /var/lib/nginx && touch /run/nginx.pid && chown -R appuser:appgroup /run/nginx.pid

RUN composer create-project -n craftcms/craft /tmp/app

RUN sed -i "s/create()/create()\n    ->requireMatchingUserAgentForSession(false)/g" /tmp/app/config/general.php

RUN rm -f /tmp/app/.env

RUN mv /tmp/app/* /app

RUN chown -R appuser:appgroup /app

# run container as the www-data user
USER appuser

# set the working directory for convenience
WORKDIR /app