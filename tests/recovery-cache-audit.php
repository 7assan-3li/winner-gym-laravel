<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$root = dirname(__DIR__).'/storage/framework/cache/data';
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);
$interesting = [];
$summary = [];

foreach ($iterator as $file) {
    if (! $file->isFile()) {
        continue;
    }

    $raw = @file_get_contents($file->getPathname());
    if ($raw === false || strlen($raw) < 11) {
        continue;
    }

    $payload = substr($raw, 10);
    $value = @unserialize($payload);
    if ($value === false && $payload !== serialize(false)) {
        $summary['unparsed'] = ($summary['unparsed'] ?? 0) + 1;

        continue;
    }

    $type = is_object($value) ? get_class($value) : gettype($value);
    $summary[$type] = ($summary[$type] ?? 0) + 1;
    $entry = [
        'file' => str_replace('\\', '/', $file->getPathname()),
        'modified' => date('Y-m-d H:i:s', $file->getMTime()),
        'type' => $type,
    ];

    if ($value instanceof Collection) {
        $entry['count'] = $value->count();
        $entry['sample'] = $value->take(3)->map(function ($item) {
            if ($item instanceof Model) {
                $item = $item->toArray();
            }

            return is_array($item)
                ? array_intersect_key($item, array_flip(['id', 'name', 'full_name', 'membership_code', 'phone', 'status', 'code']))
                : get_debug_type($item);
        })->values()->all();
        $interesting[] = $entry;

        continue;
    }

    if (! is_array($value)) {
        continue;
    }

    $stringKeys = array_values(array_filter(array_keys($value), 'is_string'));
    $wanted = [
        'stats', 'members', 'packages', 'recent_subscriptions', 'today_appointments',
        'expense_categories', 'product_categories', 'latest_payment', 'latest_member',
        'low_stock_product', 'next_appointment', 'counts', 'data',
    ];
    $hits = array_values(array_intersect($wanted, $stringKeys));
    if ($hits === []) {
        continue;
    }

    $entry['keys'] = $stringKeys;
    $entry['datasets'] = [];
    foreach ($hits as $key) {
        $dataset = $value[$key];
        $entry['datasets'][$key] = is_countable($dataset)
            ? count($dataset)
            : (is_null($dataset) ? null : get_debug_type($dataset));

        if (in_array($key, ['members', 'packages', 'recent_subscriptions', 'expense_categories', 'product_categories'], true)
            && is_array($dataset)) {
            $entry['samples'][$key] = array_slice(array_map(function ($item) {
                if (is_object($item) && method_exists($item, 'toArray')) {
                    $item = $item->toArray();
                }

                return is_array($item)
                    ? array_intersect_key($item, array_flip(['id', 'name', 'full_name', 'membership_code', 'phone', 'status', 'code', 'member_id', 'package_id']))
                    : get_debug_type($item);
            }, $dataset), 0, 3);
        }
    }
    $interesting[] = $entry;
}

echo json_encode(
    ['summary' => $summary, 'interesting' => $interesting],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
).PHP_EOL;
