WINNER GYM — Subscriptions final polish

What this patch does:
- Restores the dedicated subscriptions body class after the Members patch, so subscription-specific sizing rules apply again.
- Fixes the clipped-left page by explicitly sizing the fixed sidebar and the main canvas.
- Makes the account/owner block the right-most topbar item, then notifications and theme controls, matching the accepted Members/Dashboard composition.
- Enlarges typography, icons, cards, filters, table rows, and action buttons without making the layout overflow.
- Makes the topbar search focus the real subscription search field.
- Improves the from/to date range into two professional date controls. Clicking each field opens the browser calendar picker where supported.
- Preserves all existing Livewire search/status/package/payment/date filters and reset functionality.
- Preserves create-subscription, details, payment links/actions, PDF export, pagination, YER/SAR separation, and existing backend rules.
- Adds matching Light/Blue styling for this screen.
- No migrations. No database schema changes. No .env changes.

Install from project root:
Expand-Archive "$env:USERPROFILE\Downloads\winner-gym-subscriptions-final-patch.zip" -DestinationPath . -Force
php artisan optimize:clear

Then open:
http://winner-gym-laravel.test/subscriptions
and Ctrl+F5.
