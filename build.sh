#!/usr/bin/env bash
# الخروج فوراً في حال حدوث أي خطأ
set -o errexit

# تثبيت مكتبات Composer للإنتاج
composer install --no-dev --optimize-autoloader

# نسخ ملف البيئة إذا لم يكن موجوداً (رغم أننا سنعتمد على إعدادات الموقع لاحقاً)
cp .env.example .env

# توليد مفتاح التطبيق إذا لزم الأمر
php artisan key:generate --force

# تشغيل الميجريشن لقاعدة البيانات (اختياري، فعّليها إذا ربطتِ قاعدة بيانات)
# php artisan migrate --force

# تنظيف الكاش وتحسين الأداء
php artisan config:cache
php artisan route:cache
php artisan view:cache
