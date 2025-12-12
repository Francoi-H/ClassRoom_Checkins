<?php
require_once __DIR__ . '/bootstrap.php';
ensure_db_ready();

$hasUsersTable = false;
$error = '';

try {
    $res = $conn->query("SHOW TABLES LIKE 'users'");
    $hasUsersTable = $res && $res->num_rows > 0;
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Install | Classroom Check-in</title>
  <style>
    body{font-family:system-ui,Arial;margin:40px;max-width:900px}
    code,pre{background:#f4f4f4;border-radius:10px}
    code{padding:2px 6px}
    pre{padding:14px;overflow:auto}
    .card{border:1px solid #ddd;border-radius:14px;padding:16px}
  </style>
</head>
<body>
  <h1>Classroom Check-in Setup</h1>

  <?php if ($hasUsersTable): ?>
    <div class="card">
      Database detected. <a href="login.php">Go to Login</a>
    </div>
  <?php else: ?>
    <div class="card">
      <p><strong>Your database schema hasn’t been imported yet.</strong></p>
      <ol>
        <li>Import <code>schema.sql</code> into MySQL (phpMyAdmin or CLI).</li>
        <li>Confirm DB credentials in <code>config.php</code> (host/user/pass/db).</li>
      </ol>

      <p>CLI example:</p>
      <pre><code>mysql -u root -p &lt; schema.sql</code></pre>

      <?php if ($error): ?>
        <p><strong>Error:</strong> <?= htmlspecialchars($error) ?></p>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</body>
</html>
