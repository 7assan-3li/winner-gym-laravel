<?php

return [
    'subscriptions' => [
        'expiring_soon_days' => (int) env('SUBSCRIPTIONS_EXPIRING_SOON_DAYS', 7),
    ],

    'backups' => [
        'automated' => (bool) env('BACKUP_AUTOMATED', false),
        'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),
        'archive_password' => env('BACKUP_ARCHIVE_PASSWORD'),
    ],
];
