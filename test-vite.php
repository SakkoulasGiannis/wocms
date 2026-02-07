<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tm = app(\App\Services\ThemeManager::class);

echo "Active Theme: " . $tm->getActiveTheme() . PHP_EOL;
echo "Uses Vite: " . ($tm->usesVite() ? 'YES' : 'NO') . PHP_EOL;
echo "Metadata vite field: " . json_encode($tm->getMetadata()['vite'] ?? 'NOT SET') . PHP_EOL;
