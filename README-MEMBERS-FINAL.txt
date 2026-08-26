WINNER GYM — Members Final Patch

Changes:
- Members topbar now follows the accepted Dashboard arrangement.
- Logged-in owner/employee profile is on the far right with notifications and appearance controls.
- Member page typography/icon sizing and spacing polished.
- Eye button opens Member Details for every row.
- Three-dots More menu now works and includes details, subscription shortcut, edit, suspend/reactivate, and archive according to permissions/status.
- Archive is used instead of destructive delete to preserve linked history.
- Member Details includes a direct subscription shortcut.
- Member creation remains separate from subscription creation by design; the new shortcuts reduce the extra navigation.
- No migration and no database schema changes.
- Includes Light/Blue styling for Members while preserving Dark mode.

Install from project root after extracting over the project:
php artisan optimize:clear
Then Ctrl+F5 in browser.
