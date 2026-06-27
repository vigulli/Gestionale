<?php
/**
 * GESTIONALE — Setup wizard (da rimuovere dopo l'installazione!)
 * Carica questo file nella cartella public/ e aprilo dal browser una sola volta.
 * ELIMINALO subito dopo aver completato la configurazione.
 */

// Blocca accesso se .env è già configurato e APP_KEY è settata
$envPath = __DIR__ . '/../.env';
$envContent = file_exists($envPath) ? file_get_contents($envPath) : '';
$alreadySetup = str_contains($envContent, 'APP_KEY=base64:') && !isset($_GET['force']);

define('SETUP_VERSION', '1.0.0');

$step    = $_GET['step'] ?? '1';
$message = '';
$error   = '';

// ─── POST handlers ────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($step === '1') {
        // Crea/aggiorna .env
        $appUrl  = rtrim($_POST['app_url'] ?? '', '/');
        $dbHost  = $_POST['db_host'] ?? '127.0.0.1';
        $dbPort  = $_POST['db_port'] ?? '3306';
        $dbName  = $_POST['db_name'] ?? 'gestionale_central';
        $dbUser  = $_POST['db_user'] ?? '';
        $dbPass  = $_POST['db_pass'] ?? '';
        $appKey  = 'base64:' . base64_encode(random_bytes(32));

        $env = <<<ENV
APP_NAME="Gestionale"
APP_ENV=production
APP_KEY={$appKey}
APP_DEBUG=false
APP_URL={$appUrl}
APP_TIMEZONE=Europe/Zurich

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST={$dbHost}
DB_PORT={$dbPort}
DB_DATABASE={$dbName}
DB_USERNAME={$dbUser}
DB_PASSWORD={$dbPass}

TENANCY_DATABASE_PREFIX=wd4bm9_tenant_
TENANCY_DATABASE_USERNAME={$dbUser}
TENANCY_DATABASE_PASSWORD={$dbPass}

SESSION_DRIVER=database
SESSION_LIFETIME=480

CACHE_STORE=database

QUEUE_CONNECTION=database

MAIL_MAILER=log

FILESYSTEM_DISK=public
ENV;

        file_put_contents($envPath, $env);
        header('Location: setup.php?step=2');
        exit;
    }

    if ($step === '2') {
        // Test connessione DB centrale
        $dbHost = $_POST['db_host'] ?? '127.0.0.1';
        $dbPort = $_POST['db_port'] ?? '3306';
        $dbName = $_POST['db_name'] ?? 'gestionale_central';
        $dbUser = $_POST['db_user'] ?? '';
        $dbPass = $_POST['db_pass'] ?? '';

        try {
            $pdo = new PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $count = $pdo->query("SELECT COUNT(*) FROM tenants")->fetchColumn();
            $message = "✅ Connessione riuscita! Trovati {$count} tenant nel database.";
        } catch (Exception $e) {
            $error = '❌ Errore connessione: ' . $e->getMessage();
        }
    }

    if ($step === '3') {
        // Crea utente admin in un tenant
        $tenantDb = $_POST['tenant_db'] ?? '';
        $dbHost   = $_POST['db_host'] ?? '127.0.0.1';
        $dbPort   = $_POST['db_port'] ?? '3306';
        $dbUser   = $_POST['db_user'] ?? '';
        $dbPass   = $_POST['db_pass'] ?? '';
        $name     = $_POST['admin_name'] ?? 'Admin';
        $email    = $_POST['admin_email'] ?? '';
        $password = $_POST['admin_password'] ?? '';

        if (strlen($password) < 8) {
            $error = '❌ La password deve essere di almeno 8 caratteri.';
        } else {
            try {
                $pdo = new PDO("mysql:host={$dbHost};port={$dbPort};dbname={$tenantDb};charset=utf8mb4", $dbUser, $dbPass);
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE password=?");
                $stmt->execute([$name, $email, $hash, $hash]);
                $message = "✅ Utente admin creato per il database {$tenantDb}!";
            } catch (Exception $e) {
                $error = '❌ Errore: ' . $e->getMessage();
            }
        }
    }
}

// ─── Leggi .env per pre-compilare i form ──────────────────────────────────────
function envVal(string $key, string $default = ''): string {
    global $envContent;
    preg_match('/^' . preg_quote($key, '/') . '=(.*)$/m', $envContent, $m);
    return trim($m[1] ?? $default, '"\'');
}

$savedDbHost = envVal('DB_HOST', '127.0.0.1');
$savedDbPort = envVal('DB_PORT', '3306');
$savedDbName = envVal('DB_DATABASE', 'gestionale_central');
$savedDbUser = envVal('DB_USERNAME', '');
$savedDbPass = envVal('DB_PASSWORD', '');
$savedAppUrl = envVal('APP_URL', '');

?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Gestionale — Setup Wizard</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f1f5f9; color: #1e293b; }
  .container { max-width: 600px; margin: 40px auto; padding: 0 16px; }
  .card { background: white; border-radius: 16px; padding: 32px; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
  h1 { font-size: 24px; font-weight: 800; color: #6366f1; margin-bottom: 4px; }
  h2 { font-size: 18px; font-weight: 700; margin-bottom: 16px; color: #1e293b; }
  .subtitle { color: #64748b; font-size: 14px; margin-bottom: 24px; }
  label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 4px; margin-top: 14px; }
  input, select { width: 100%; padding: 10px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 14px; outline: none; transition: border .15s; }
  input:focus, select:focus { border-color: #6366f1; }
  .btn { display: block; width: 100%; padding: 12px; background: #6366f1; color: white; border: none; border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer; margin-top: 24px; }
  .btn:hover { background: #4f46e5; }
  .steps { display: flex; gap: 8px; margin-bottom: 24px; }
  .step { flex: 1; height: 4px; border-radius: 4px; background: #e2e8f0; }
  .step.active { background: #6366f1; }
  .step.done { background: #10b981; }
  .msg { padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; }
  .msg.ok { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
  .msg.err { background: #fef2f2; color: #991b1b; border: 1px solid #fca5a5; }
  .warn { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-top: 20px; }
  .small { font-size: 12px; color: #64748b; margin-top: 4px; }
</style>
</head>
<body>
<div class="container">
  <div class="card">
    <h1>⚙️ Gestionale Setup</h1>
    <p class="subtitle">Configurazione guidata — seguire i passi nell'ordine</p>

    <div class="steps">
      <div class="step <?= $step >= 1 ? ($step > 1 ? 'done' : 'active') : '' ?>"></div>
      <div class="step <?= $step >= 2 ? ($step > 2 ? 'done' : 'active') : '' ?>"></div>
      <div class="step <?= $step >= 3 ? 'active' : '' ?>"></div>
    </div>

    <?php if ($message): ?>
      <div class="msg ok"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="msg err"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($step === '1'): ?>
      <h2>Passo 1 — Configurazione .env</h2>
      <form method="POST" action="setup.php?step=1">
        <label>URL del sito (es. https://gestionale.miodominio.ch)</label>
        <input name="app_url" type="url" required value="<?= htmlspecialchars($savedAppUrl ?: 'https://') ?>">

        <label>Host database</label>
        <input name="db_host" value="<?= htmlspecialchars($savedDbHost) ?>" required>
        <p class="small">Di solito "127.0.0.1" o "localhost" su Infomaniak</p>

        <label>Porta database</label>
        <input name="db_port" value="<?= htmlspecialchars($savedDbPort) ?>">

        <label>Nome database CENTRALE</label>
        <input name="db_name" value="<?= htmlspecialchars($savedDbName) ?>" required>
        <p class="small">Es. gestionale_central — crealo prima in phpMyAdmin</p>

        <label>Utente MySQL</label>
        <input name="db_user" value="<?= htmlspecialchars($savedDbUser) ?>" required>

        <label>Password MySQL</label>
        <input name="db_pass" type="password" value="<?= htmlspecialchars($savedDbPass) ?>">

        <button class="btn" type="submit">Salva configurazione → Passo 2</button>
      </form>

    <?php elseif ($step === '2'): ?>
      <h2>Passo 2 — Verifica database</h2>
      <p class="subtitle">Assicurati di aver importato i file SQL via phpMyAdmin prima di procedere.</p>

      <form method="POST" action="setup.php?step=2">
        <input type="hidden" name="db_host" value="<?= htmlspecialchars($savedDbHost) ?>">
        <input type="hidden" name="db_port" value="<?= htmlspecialchars($savedDbPort) ?>">
        <input type="hidden" name="db_name" value="<?= htmlspecialchars($savedDbName) ?>">
        <input type="hidden" name="db_user" value="<?= htmlspecialchars($savedDbUser) ?>">
        <input type="hidden" name="db_pass" value="<?= htmlspecialchars($savedDbPass) ?>">
        <button class="btn" type="submit" style="background:#10b981;">Testa connessione DB</button>
      </form>

      <br>
      <a href="setup.php?step=3" style="display:block;text-align:center;color:#6366f1;font-weight:600;">Connessione OK → Passo 3 (crea admin)</a>

    <?php elseif ($step === '3'): ?>
      <h2>Passo 3 — Crea utente admin</h2>
      <p class="subtitle">Crea un utente admin in ciascun database tenant. Ripeti per i-lab, nipotetech, dtflab.</p>

      <form method="POST" action="setup.php?step=3">
        <input type="hidden" name="db_host" value="<?= htmlspecialchars($savedDbHost) ?>">
        <input type="hidden" name="db_port" value="<?= htmlspecialchars($savedDbPort) ?>">
        <input type="hidden" name="db_user" value="<?= htmlspecialchars($savedDbUser) ?>">
        <input type="hidden" name="db_pass" value="<?= htmlspecialchars($savedDbPass) ?>">

        <label>Database tenant</label>
        <select name="tenant_db">
          <option value="wd4bm9_tenant_ilab">wd4bm9_tenant_ilab (i-Lab)</option>
          <option value="wd4bm9_tenant_nipotetech">wd4bm9_tenant_nipotetech (NipoteTech)</option>
          <option value="wd4bm9_tenant_dtflab">wd4bm9_tenant_dtflab (DTF Lab)</option>
        </select>

        <label>Nome admin</label>
        <input name="admin_name" value="Admin" required>

        <label>Email admin</label>
        <input name="admin_email" type="email" required placeholder="admin@miodominio.ch">

        <label>Password admin (min. 8 caratteri)</label>
        <input name="admin_password" type="password" required minlength="8">

        <button class="btn" type="submit">Crea utente admin</button>
      </form>

      <div class="warn">
        ⚠️ <strong>IMPORTANTE:</strong> dopo aver creato tutti gli utenti, <strong>elimina questo file</strong>
        <code>public/setup.php</code> dal server per sicurezza!
      </div>

    <?php endif; ?>
  </div>
</div>
</body>
</html>
