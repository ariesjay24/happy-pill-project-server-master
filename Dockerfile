FROM richarvey/nginx-php-fpm:3.1.6

COPY . .

# Image config
ENV SKIP_COMPOSER 1
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

# Laravel config
ENV APP_ENV production
ENV APP_DEBUG false
ENV DB_CONNECTION pgsql
ENV DB_HOST dpg-cqkdpmjqf0us73c84np0-a.singapore-postgres.render.com
ENV DB_PORT 5432
ENV DB_DATABASE happypill_sq15
ENV DB_USERNAME root
ENV DB_PASSWORD PbIVSUeTR37ikURzdEAxRXkuo7GyqYIz

# Allow composer to run as root
ENV COMPOSER_ALLOW_SUPERUSER 1

RUN composer install --no-dev --optimize-autoloader

CMD ["/start.sh"]