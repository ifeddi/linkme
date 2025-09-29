FROM php:8.3-cli-bullseye

# Mise à jour et installation des dépendances
RUN apt-get update -o Acquire::ForceIPv4=true \
    && apt-get install -y git unzip libicu-dev libpq-dev libzip-dev zip \
    && docker-php-ext-install intl pdo pdo_mysql opcache \
    && rm -rf /var/lib/apt/lists/*

# Activer et configurer OPcache (fortement recommandé en prod et pour accélérer en dev sous Windows)
RUN set -eux; \
    { \
      echo "opcache.enable=1"; \
      echo "opcache.enable_cli=1"; \
      echo "opcache.memory_consumption=256"; \
      echo "opcache.interned_strings_buffer=16"; \
      echo "opcache.max_accelerated_files=20000"; \
      echo "opcache.validate_timestamps=0"; \
      echo "opcache.save_comments=1"; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html
