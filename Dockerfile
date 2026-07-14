FROM php:8.2-cli

RUN apt-get update && apt-get install -y sqlite3 libsqlite3-dev && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install pdo_sqlite

WORKDIR /app
COPY . .

EXPOSE 8080
CMD php -S 0.0.0.0:${PORT:-8080} -t public
