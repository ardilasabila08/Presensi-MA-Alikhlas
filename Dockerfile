FROM php:8.1-apache

# Install ekstensi mysqli agar koneksi database aktif
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Aktifkan mod_rewrite Apache jika nanti dibutuhkan
RUN a2enmod rewrite

# Salin semua file project ke dalam folder web server Apache
COPY . /var/www/html/

# Ubah Hak Akses agar terbaca dengan baik
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html