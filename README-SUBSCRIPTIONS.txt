WINNER GYM — Subscriptions reference patch

This patch changes only the subscriptions UI/query presentation and the application shell branch for /subscriptions.
No migrations. No .env changes. No database reset.

Files:
- app/Livewire/Subscriptions/Index.php
- resources/views/livewire/subscriptions/index.blade.php
- resources/views/layouts/app/sidebar.blade.php
- resources/css/app.css

Install from project root:
Expand-Archive "$env:USERPROFILE\Downloads\winner-gym-subscriptions-reference-patch.zip" -DestinationPath . -Force
npm run build
php artisan optimize:clear

Then open:
http://winner-gym-laravel.test/subscriptions
and press Ctrl+F5.
