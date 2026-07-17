# Используем официальный образ PHP с Apache
FROM php:8.2-apache

# Устанавливаем системные зависимости и расширение для PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pdo_pgsql

# Копируем весь код в контейнер
COPY . /var/www/html/

# Устанавливаем права (Apache работает от www-data)
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# Открываем порт 80
EXPOSE 80