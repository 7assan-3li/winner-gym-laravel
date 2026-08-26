WINNER GYM — Finance Final V2

This patch updates the financial section to match the accepted Dashboard / Members / Subscriptions / Attendance style.

Updated files:
- resources/views/layouts/app/sidebar.blade.php
- public/winner-gym/finance-final.css
- app/Livewire/Finance/ExpensesIndex.php
- app/Livewire/Finance/PaymentsIndex.php
- resources/views/livewire/finance/expenses-index.blade.php
- resources/views/livewire/finance/payments-index.blade.php

What is included:
- Dedicated finance top bar: Finance title/breadcrumb, centered finance search, theme, notifications/audit, dynamic current user menu.
- Full-width route-scoped finance layout (fixes the squeezed/empty-right-side problem).
- Professional responsive RTL finance dashboard visual system.
- Expenses tab: revenue/expense/net-profit KPIs, month/today totals, cancelled counts, financial flow chart, expense category distribution, filters, expense ledger, financial summaries, latest expenses.
- Add Expense modal: category, amount, YER/SAR, date, cash/transfer, transfer reference, notes, private receipt upload.
- Expense cancellation keeps the financial record and requires a reason.
- Payments tab: revenue/expense/net-profit KPIs, monthly received, pending installments, completed/reversed counts, flow chart, revenue distribution, payments ledger.
- Receive Payment selector modal: search pending/overdue installments by member, phone or membership code, then confirm payment.
- Payment reversal and subscription refund flows retained.
- Quick links between expenses and payments.
- Query shortcuts:
  /expenses?new=1 opens Add Expense automatically (if permitted).
  /payments?receive=1 opens Receive Payment automatically (if permitted).

Install from the project root in PowerShell:
cd "C:\Users\Omer AL-Kaff\Herd\winner-gym-laravel"
Expand-Archive "$env:USERPROFILE\Downloads\winner-gym-finance-final-v2.zip" -DestinationPath . -Force
php artisan optimize:clear

Then hard refresh once with Ctrl + F5 and open:
http://winner-gym-laravel.test/expenses
http://winner-gym-laravel.test/payments

No migration is included.
Do NOT run migrate:fresh.
No npm build is required because finance-final.css is a public route-scoped stylesheet.
