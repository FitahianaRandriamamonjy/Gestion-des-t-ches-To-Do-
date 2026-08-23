FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libpq-dev nginx \
    && docker-php-ext-install intl pdo pdo_pgsql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

ENV APP_ENV=prod

RUN composer install --optimize-autoloader


RUN chown -R www-data:www-data var/


RUN echo 'server { \
    listen 80; \
    root /var/www/html/public; \
    location / { try_files $uri /index.php$is_args$args; } \
    location ~ ^/index.php(/|$) { \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_split_path_info ^(.+?\.php)(/.*)$; \
        include fastcgi_params; \
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name; \
        fastcgi_param DOCUMENT_ROOT $realpath_root; \
        internal; \
    } \
    location ~ \.php$ { return 404; } \
}' > /etc/nginx/sites-available/default

EXPOSE 80

CMD php-fpm -D && nginx -g "daemon off;"