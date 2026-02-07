<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tm = app(\App\Services\ThemeManager::class);

echo "Active Theme: " . $tm->getActiveTheme() . PHP_EOL;
echo "Layout Path: " . $tm->getLayout() . PHP_EOL;
echo "Layout Exists: " . (view()->exists($tm->getLayout()) ? 'YES' : 'NO') . PHP_EOL;
echo PHP_EOL;
echo "Metadata: " . json_encode($tm->getMetadata(), JSON_PRETTY_PRINT) . PHP_EOL;
echo PHP_EOL;
echo "Available Themes: " . json_encode(array_keys($tm->getAvailableThemes()), JSON_PRETTY_PRINT) . PHP_EOL;
