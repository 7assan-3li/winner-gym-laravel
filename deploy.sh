#!/bin/bash
set -e

echo "=========================================="
echo "🚀 Starting Automated Deployment Pipeline"
echo "=========================================="

# الانتقال لمجلد المشروع
cd "$(dirname "$0")"

# 1. سحب أحدث كود من GitHub (في حالة التشغيل اليدوي)
if [ -z "$CI" ] && [ -z "$GITHUB_ACTIONS" ]; then
    echo "📥 Pulling latest changes from Git..."
    git fetch origin main
    git reset --hard origin/main
fi

# 2. تحديث حزم Composer للإنتاج
echo "📦 Installing Composer dependencies..."
docker compose run --rm app composer install --no-dev --optimize-autoloader --no-interaction

# 3. بناء ملفات الواجهة
echo "🎨 Building frontend assets..."
docker compose run --rm app npm install --no-audit
docker compose run --rm app npm run build

# 4. ترحيل قاعدة البيانات بأمان
echo "🗄️ Running database migrations..."
docker compose run --rm app php artisan migrate --force

# 5. تحسين وتوليد الكاش لسرعة استجابة النظام
echo "⚡ Rebuilding production caches..."
docker compose run --rm app php artisan optimize:clear
docker compose run --rm app php artisan config:cache
docker compose run --rm app php artisan route:cache
docker compose run --rm app php artisan view:cache
docker compose run --rm app php artisan event:cache

# 6. تصحيح الصلاحيات لضمان عمل الحاويات ومزامنة Git بسلاسة
echo "🔒 Fixing storage & cache permissions..."
docker compose exec -T app chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache || true
chmod -R 777 storage bootstrap/cache 2>/dev/null || true

# 7. تطبيق التغييرات وإعادة تشغيل PHP-FPM, Queue Workers والمجدول
echo "🔄 Updating & Restarting PHP-FPM, Queue Workers & Scheduler..."
docker compose exec -T app php artisan queue:restart || true
docker compose up -d app queue scheduler
docker compose restart app queue scheduler

# 8. إعادة تحميل خادم Nginx بنعومة
echo "🌐 Reloading Web server..."
docker compose exec -T web nginx -s reload || docker compose restart web

# 9. تنظيف بقايا الدوكر القديمة لتوفير مساحة القرص
echo "🧹 Pruning unused Docker images..."
docker image prune -f

echo "=========================================="
echo "✅ Deployment completed successfully!"
echo "=========================================="
