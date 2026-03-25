# =============================================================================
# Prestiter Logger — Development / CI image
# PHP 7.4 (minimum supported version per composer.json) on Alpine 3.16
# NOT intended for production use.
# =============================================================================

# Pin both PHP patch version and Alpine version for reproducible builds.
# Alpine 3.16 is the last Alpine that ships php:7.4 in the official PHP images.
FROM php:7.4.33-cli-alpine3.16

LABEL maintainer="Alfabiz srl <info@prestiter.it>"
LABEL description="Development/CI environment for Prestiter Logger (PHP 7.4)"

# =============================================================================
# Build-time argument: set WITH_XDEBUG=1 to install Xdebug for coverage.
# Default: 0 (disabled) — keeps the image smaller in pure test runs.
# Usage: docker build --build-arg WITH_XDEBUG=1 .
# =============================================================================
ARG WITH_XDEBUG=0

# =============================================================================
# System dependencies
# Use --virtual to group build-only packages so they can be removed afterward,
# keeping the final layer lighter.
# =============================================================================
RUN apk add --no-cache \
        git \
        curl \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
    # Compile always-needed PHP extensions
    && docker-php-ext-install -j"$(nproc)" pcntl sockets \
    # Install Xdebug only when requested
    && if [ "$WITH_XDEBUG" = "1" ]; then \
        pecl install xdebug-3.1.6 \
        && docker-php-ext-enable xdebug \
        && echo "xdebug.mode=coverage" \
            >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
    fi \
    # Remove build-only packages to shrink the image
    && apk del .build-deps

# =============================================================================
# Composer — pin to a specific version for reproducibility.
# 2.2.x is the last LTS series that explicitly supports PHP 7.x.
# =============================================================================
COPY --from=composer:2.2.25 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# =============================================================================
# Layer-cache optimisation: copy only the manifest first so that the
# `composer install` layer is invalidated only when dependencies change,
# not on every source-code edit.
# =============================================================================
COPY composer.json composer.lock* ./
RUN composer install --prefer-dist --no-interaction --no-scripts --no-autoloader

# Copy source and regenerate the full autoloader with dev classes
COPY . .
RUN composer dump-autoload --dev

CMD ["php", "-v"]
