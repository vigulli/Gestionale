<?php
$files = [
    'build/assets/app.js',
    'build/assets/AppLayout.css',
    'build/manifest.json',
    '../resources/views/app.blade.php',
    '../.env',
    '../vendor/autoload.php',
];

echo "<h2>File Check</h2><ul>";
foreach ($files as $f) {
    $path = __DIR__ . '/' . $f;
    $exists = file_exists($path);
    $size = $exists ? filesize($path) : 0;
    $color = $exists ? 'green' : 'red';
    echo "<li style='color:$color'>" . ($exists ? "✓" : "✗") . " $f" . ($exists ? " ({$size} bytes)" : " MANCANTE") . "</li>";
}
echo "</ul>";

// Mostra contenuto app.blade.php
$blade = __DIR__ . '/../resources/views/app.blade.php';
if (file_exists($blade)) {
    echo "<h2>app.blade.php:</h2><pre>" . htmlspecialchars(file_get_contents($blade)) . "</pre>";
}

// Mostra .env (senza password)
$env = __DIR__ . '/../.env';
if (file_exists($env)) {
    $content = file_get_contents($env);
    $content = preg_replace('/PASSWORD=.*/m', 'PASSWORD=***', $content);
    echo "<h2>.env:</h2><pre>" . htmlspecialchars($content) . "</pre>";
}
