#!/bin/bash
set -e

echo "=========================================="
echo "🚀 Starting Automated Deployment Pipeline"
echo "=========================================="

# الانتقال لمجلد المشروع
cd "$(dirname "$0")"

# 1. سحب أحدث كود من GitHub
echo "📥 Pulling latest changes from Git..."
git pull origin main

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

# 6. إعادة تشغيل الـ Queue Workers والمجدول
echo "🔄 Restarting Queue Workers & Scheduler..."
docker compose exec -T app php artisan queue:restart || true
docker compose restart queue scheduler

# 7. إعادة تحميل خادم Nginx بنعومة
echo "🌐 Reloading Web server..."
docker compose exec -T web nginx -s reload || docker compose restart web

# 8. تنظيف بقايا الدوكر القديمة لتوفير مساحة القرص
echo "🧹 Pruning unused Docker images..."
docker image prune -f

echo "=========================================="
echo "✅ Deployment completed successfully!"
echo "=========================================="
