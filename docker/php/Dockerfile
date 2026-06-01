FROM php:8.2-fpm

# System dependencies:
#  - ghostscript            -> SVG/PDF -> EPS conversion
#  - imagemagick + librsvg  -> SVG -> JPEG rasterization (rsvg gives clean SVG)
#  - libzip / libpng / etc. -> PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip \
        ghostscript \
        imagemagick libmagickwand-dev \
        librsvg2-bin \
        libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd zip pdo_mysql mbstring \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && rm -rf /var/lib/apt/lists/*

# Allow ImageMagick to read/write PDF & PS (Debian ships a restrictive policy).
RUN if [ -f /etc/ImageMagick-6/policy.xml ]; then \
        sed -i 's/rights="none" pattern="PDF"/rights="read|write" pattern="PDF"/' /etc/ImageMagick-6/policy.xml; \
        sed -i 's/rights="none" pattern="PS"/rights="read|write" pattern="PS"/' /etc/ImageMagick-6/policy.xml; \
    fi

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

EXPOSE 9000
CMD ["php-fpm"]
