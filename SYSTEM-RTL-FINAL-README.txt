WINNER GYM — System RTL Final Stabilization

Fixes:
- Sidebar/logo/navigation are one global Arabic shell on the RIGHT for every authenticated screen.
- Main canvas shifts correctly with the sidebar; no clipped, squeezed, or mixed left/right screens.
- One shared topbar is used everywhere: page title, centered search, theme, notifications, current user.
- Theme/notification/account placement is no longer duplicated per module.
- Route styling is loaded on every authenticated request so owner/staff full reloads match.
- Staff sees the same UI as owner for allowed modules; only permissions hide/deny modules/actions.
- Core routes accept the appropriate view/manage permissions consistently.
- Admin tabs remain permission-aware.
- Reports/admin/settings functional screens receive the same full-width dark shell.

No database migration is included or required.
No npm build is required.
