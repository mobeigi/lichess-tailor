# Stage 1: php-fpm
FROM php:8-fpm-alpine AS php-fpm
WORKDIR /var/www/html
COPY . .

# Stage 2: Nginx setup
FROM nginx:alpine AS nginx
COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY . /var/www/html
