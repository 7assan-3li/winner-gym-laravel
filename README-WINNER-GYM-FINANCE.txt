WINNER GYM — Finance UI Patch

What this patch updates:
- /payments: financial/payments screen in the approved dark-blue WINNER GYM style.
- /expenses: dedicated expenses branch using the same visual system.
- Tabs link between Payments and Expenses.
- Expense create modal with category, title, amount, currency, date, cash/transfer, transfer reference, notes, and optional private receipt upload.
- Expense cancellation keeps the financial record and requires a reason.
- YER and SAR are always filtered and displayed separately.
- Payments keep existing business services for installment payment, reversal, and refund.
- No database migration is included or required.

Install from project root:
Expand-Archive "$env:USERPROFILE\Downloads\winner-gym-finance-complete-patch.zip" -DestinationPath . -Force
npm run build
php artisan optimize:clear

Open:
http://winner-gym-laravel.test/payments
http://winner-gym-laravel.test/expenses

DO NOT run migrate:fresh.
