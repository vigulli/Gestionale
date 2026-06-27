<?php
/**
 * Fix password admin — ELIMINA DOPO L'USO
 */
$envPath = __DIR__ . '/../.env';
$env = file_exists($envPath) ? file_get_contents($envPath) : '';

function envVal($key, $default = '') {
    global $env;
    preg_match('/^' . preg_quote($key, '/') . '=(.*)$/m', $env, $m);
    return trim($m[1] ?? $default, '"\'');
}

$host   = envVal('DB_HOST', '127.0.0.1');
$port   = envVal('DB_PORT', '3306');
$dbname = envVal('DB_DATABASE', '');
$user   = envVal('DB_USERNAME', '');
$pass   = envVal('DB_PASSWORD', '');

$newPassword = $_POST['password'] ?? '';
$email       = $_POST['email'] ?? '';
$result      = '';
$users       = [];

try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Mostra utenti esistenti
    $users = $pdo->query("SELECT id, name, email, LEFT(password,10) as pwd_start, created_at FROM users")->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $email && strlen($newPassword) >= 6) {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hash, $email]);
        $affected = $stmt->rowCount();
        if ($affected > 0) {
            $result = "✅ Password aggiornata per {$email}. Ora prova il login.";
            // Rileggi
            $users = $pdo->query("SELECT id, name, email, LEFT(password,10) as pwd_start, created_at FROM users")->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $result = "⚠️ Nessun utente trovato con email {$email}.";
        }
    }
} catch (Exception $e) {
    $result = '❌ Errore DB: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Fix Password</title>
<style>
body { font-family: sans-serif; max-width: 600px; margin: 40px auto; padding: 0 16px; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th, td { padding: 8px 12px; border: 1px solid #e2e8f0; text-align: left; font-size: 13px; }
th { background: #f8fafc; }
input { width: 100%; padding: 8px; margin: 4px 0 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; }
button { background: #6366f1; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-size: 15px; cursor: pointer; }
.ok { background: #ecfdf5; color: #065f46; padding: 12px; border-radius: 8px; margin: 16px 0; }
.err { background: #fef2f2; color: #991b1b; padding: 12px; border-radius: 8px; margin: 16px 0; }
.warn { background: #fffbeb; color: #92400e; padding: 10px; border-radius: 8px; margin-top: 20px; font-size: 13px; }
</style>
</head>
<body>
<h2>Fix Password Admin</h2>
<p>Database: <strong><?= htmlspecialchars($dbname) ?></strong></p>

<?php if ($result): ?>
  <div class="<?= str_contains($result, '✅') ? 'ok' : 'err' ?>"><?= htmlspecialchars($result) ?></div>
<?php endif; ?>

<h3>Utenti nel database centrale</h3>
<?php if ($users): ?>
<table>
  <tr><th>ID</th><th>Nome</th><th>Email</th><th>Password (primi 10 chr)</th><th>Creato</th></tr>
  <?php foreach ($users as $u): ?>
  <tr>
    <td><?= htmlspecialchars($u['id']) ?></td>
    <td><?= htmlspecialchars($u['name']) ?></td>
    <td><?= htmlspecialchars($u['email']) ?></td>
    <td><code><?= htmlspecialchars($u['pwd_start']) ?></code></td>
    <td><?= htmlspecialchars($u['created_at']) ?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php else: ?>
<p>Nessun utente trovato.</p>
<?php endif; ?>

<h3>Imposta nuova password</h3>
<form method="POST">
  <label>Email utente</label>
  <input name="email" type="email" value="<?= htmlspecialchars($email ?: ($users[0]['email'] ?? '')) ?>" required>
  <label>Nuova password</label>
  <input name="password" type="text" placeholder="min. 6 caratteri" required>
  <button type="submit">Aggiorna password</button>
</form>

<div class="warn">⚠️ <strong>ELIMINA QUESTO FILE</strong> dal server dopo l'uso: <code>public/fix_password.php</code></div>
</body>
</html>
