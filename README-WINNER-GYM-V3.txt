WINNER GYM — Header + Finance separation v3

Global shell:
- Sidebar stays on the RIGHT.
- Page/module title + breadcrumb are on the RIGHT.
- Search remains centered.
- Signed-in account is on the far LEFT, followed by notification and appearance controls.

Expenses:
- Expense-only KPIs: total, month, today, cash, transfer, cancelled.
- Expense trend only.
- Category distribution + expense ledger + create/cancel remain functional.

Payments:
- Collection KPIs: total collection, subscriptions, nutrition, product sales, month collection, pending installments.
- Collection trend only.
- Revenue-source distribution + installments + refunds + payment ledger remain functional.

Correctness fix:
- appointment_payments statuses are paid/reversed, so nutrition revenue now uses status=paid.

No migration. No npm build required.
