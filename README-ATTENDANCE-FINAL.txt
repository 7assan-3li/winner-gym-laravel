WINNER GYM — Attendance final polish

What this patch changes:
- Adds a dedicated Attendance top bar matching Dashboard / Members / Subscriptions.
- Keeps manager profile, theme toggle and notification/system icon on the right.
- Adds Attendance title + breadcrumb.
- Enlarges typography, controls, table rows, cards and action buttons.
- Adds a visible “تسجيل الحضور” submit button next to camera scanning.
- Keeps existing Livewire attendance logic, filters, export, camera, member details and quick actions intact.
- Adds route-scoped CSS only for /attendance to avoid changing the accepted Members/Subscriptions pages.

Install from PowerShell inside the project:
Expand-Archive "$env:USERPROFILE\Downloads\winner-gym-attendance-final.zip" -DestinationPath . -Force
php artisan optimize:clear

If your browser still shows cached styles, press Ctrl+F5 once.
