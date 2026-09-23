FROM php:8.4-fpm

# System dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    curl \
    ca-certificates \
    gnupg \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Add PostgreSQL official repository
RUN install -d /usr/share/postgresql-common/pgdg \
    && curl -o /usr/share/postgresql-common/pgdg/apt.postgresql.org.asc \
       https://www.postgresql.org/media/keys/ACCC4CF8.asc \
    && echo "deb [signed-by=/usr/share/postgresql-common/pgdg/apt.postgresql.org.asc] https://apt.postgresql.org/pub/repos/apt trixie-pgdg main" \
       > /etc/apt/sources.list.d/pgdg.list

# Install PostgreSQL 18 client + PHP extensions + Redis
RUN apt-get update && apt-get install -y \
    postgresql-client-18 \
    && docker-php-ext-install pdo pdo_pgsql \
    && docker-php-ext-install pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

# ⬅️ إعدادات PHP لرفع الملفات الكبيرة والذاكرة (جديد)
RUN echo "upload_max_filesize = 25M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 25M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
