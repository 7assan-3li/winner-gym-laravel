<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Collection;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$buckets = [
    'members' => [],
    'packages' => [],
    'subscriptions' => [],
    'payments' => [],
    'products' => [],
    'expense_categories' => [],
    'product_categories' => [],
    'appointments' => [],
];
$snapshots = 0;
$latestSnapshot = null;

$merge = static function (string $bucket, mixed $record) use (&$buckets): void {
    if (is_object($record) && method_exists($record, 'toArray')) {
        $record = $record->toArray();
    }
    if (! is_array($record) || ! isset($record['id'])) {
        return;
    }

    $id = (string) $record['id'];
    if (! isset($buckets[$bucket][$id]) || count(array_filter($record, static fn ($value) => $value !== null && $value !== ''))
        > count(array_filter($buckets[$bucket][$id], static fn ($value) => $value !== null && $value !== ''))) {
        $buckets[$bucket][$id] = $record;
    }
};

$root = dirname(__DIR__).'/storage/framework/cache/data';
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

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
        continue;
    }

    if ($value instanceof Collection) {
        foreach ($value as $record) {
            $merge('packages', $record);
        }

        continue;
    }
    if (! is_array($value) || ! array_key_exists('stats', $value) || ! array_key_exists('members', $value)) {
        continue;
    }

    $snapshots++;
    $modified = $file->getMTime();
    if ($latestSnapshot === null || $modified > $latestSnapshot) {
        $latestSnapshot = $modified;
    }

    foreach ($value['members'] ?? [] as $record) {
        $merge('members', $record);
    }
    foreach ($value['packages'] ?? [] as $record) {
        $merge('packages', $record);
    }
    foreach ($value['recent_subscriptions'] ?? [] as $record) {
        $merge('subscriptions', $record);
    }
    foreach ($value['today_appointments'] ?? [] as $record) {
        $merge('appointments', $record);
    }
    foreach ($value['expense_categories'] ?? [] as $record) {
        $merge('expense_categories', $record);
    }
    foreach ($value['product_categories'] ?? [] as $record) {
        $merge('product_categories', $record);
    }
    $merge('payments', $value['latest_payment'] ?? null);
    $merge('members', $value['latest_member'] ?? null);
    $merge('products', $value['low_stock_product'] ?? null);
    $merge('appointments', $value['next_appointment'] ?? null);
}

$result = [
    'dashboard_cache_snapshots' => $snapshots,
    'latest_snapshot' => $latestSnapshot ? date('Y-m-d H:i:s', $latestSnapshot) : null,
    'recoverable_counts' => array_map('count', $buckets),
    'records' => [],
];

foreach ($buckets as $bucket => $records) {
    $result['records'][$bucket] = array_values(array_map(static function (array $record): array {
        $relations = [];
        foreach ($record as $key => $value) {
            if (is_array($value)) {
                $relations[$key] = [
                    'count' => array_is_list($value) ? count($value) : 1,
                    'keys' => array_is_list($value) || $value === [] ? [] : array_keys($value),
                ];
                unset($record[$key]);
            }
        }
        $record['_field_count'] = count($record);
        $record['_relations'] = $relations;

        return $record;
    }, $records));
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;
