<?php
/**
 * Clear Laravel cache files — ELIMINA DOPO L'USO
 */
$basePath = dirname(__DIR__) . '/bootstrap/cache';
$deleted = [];
$errors = [];

$files = glob($basePath . '/*.php');
foreach ($files as $file) {
    if (basename($file) === '.gitignore') continue;
    if (unlink($file)) {
        $deleted[] = basename($file);
    } else {
        $errors[] = basename($file);
    }
}

// Mostra anche le route definite
$routesPath = dirname(__DIR__) . '/routes/web.php';
$webContent = file_exists($routesPath) ? file_get_contents($routesPath) : 'FILE NON TROVATO';

// Mostra la versione di web.php sul server
preg_match_all('/->name\([\'"]([^\'"]+)[\'"]\)/', $webContent, $matches);
$routeNames = $matches[1] ?? [];
?>
<!DOCTYPE html>
<html lang="it">
<head><meta charset="UTF-8"><title>Clear Cache</title>
<style>body{font-family:sans-serif;max-width:700px;margin:40px auto;padding:0 16px}
.ok{background:#ecfdf5;color:#065f46;padding:12px;border-radius:8px;margin:8px 0}
.err{background:#fef2f2;color:#991b1b;padding:12px;border-radius:8px;margin:8px 0}
.warn{background:#fffbeb;color:#92400e;padding:10px;border-radius:8px;margin-top:20px;font-size:13px}
pre{background:#f8fafc;padding:12px;border-radius:8px;font-size:12px;overflow:auto}
</style>
</head>
<body>
<h2>Clear Laravel Cache</h2>

<h3>Cache eliminata</h3>
<?php if ($deleted): ?>
  <?php foreach ($deleted as $f): ?>
    <div class="ok">✅ Eliminato: <?= htmlspecialchars($f) ?></div>
  <?php endforeach; ?>
<?php else: ?>
  <div class="ok">ℹ️ Nessun file di cache trovato (già pulita)</div>
<?php endif; ?>

<?php if ($errors): ?>
  <?php foreach ($errors as $f): ?>
    <div class="err">❌ Impossibile eliminare: <?= htmlspecialchars($f) ?></div>
  <?php endforeach; ?>
<?php endif; ?>

<h3>Route definite in routes/web.php sul server</h3>
<?php if ($routeNames): ?>
  <ul>
    <?php foreach ($routeNames as $name): ?>
      <li><code><?= htmlspecialchars($name) ?></code> <?= $name === 'tenant.select' ? '✅' : '' ?></li>
    <?php endforeach; ?>
  </ul>
  <?php if (!in_array('tenant.select', $routeNames)): ?>
    <div class="err">❌ Route "tenant.select" NON TROVATA nel web.php sul server — devi caricare la versione aggiornata del file!</div>
  <?php else: ?>
    <div class="ok">✅ Route "tenant.select" trovata.</div>
  <?php endif; ?>
<?php else: ?>
  <div class="err">❌ Nessuna route trovata — controlla routes/web.php</div>
<?php endif; ?>

<h3>Contenuto routes/web.php sul server</h3>
<pre><?= htmlspecialchars($webContent) ?></pre>

<div class="warn">⚠️ <strong>ELIMINA QUESTO FILE</strong> dopo l'uso: <code>public/clear_cache.php</code></div>
</body>
</html>
