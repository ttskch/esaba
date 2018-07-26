FROM composer:1.5 AS composer
MAINTAINER kokuyou<kokuyouwind+esaba@gmail.com>
WORKDIR /app
ADD composer.json /app/
RUN composer install --no-scripts

FROM node:9.3-alpine AS assets
WORKDIR /app
COPY --from=composer /app /app
ADD package.json /app/
RUN npm install
ADD webpack.config.js /app/
ADD assets /app/assets/
RUN NODE_ENV=production npm run build

FROM php:7.1-apache
RUN apt-get update \
  && apt-get -y install vim less \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*
RUN a2enmod rewrite
COPY --from=assets /app /app
ADD . /app/
RUN rm -rf /var/www/html && ln -s /app/web /var/www/html
RUN chmod a+w -R /app/var/cache
