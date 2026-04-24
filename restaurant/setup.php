<?php
// ONE-TIME SETUP SCRIPT - Run once then delete or protect!

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'restaurant_menu';

$done = [];
$errors = [];

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $sql = file_get_contents(__DIR__ . '/install.sql');
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $stmt) {
        if (empty($stmt)) continue;
        try {
            $pdo->exec($stmt);
            $done[] = mb_substr($stmt, 0, 60) . '...';
        } catch (PDOException $e) {
            $errors[] = $e->getMessage();
        }
    }

    $status = 'success';
} catch (PDOException $e) {
    $status = 'error';
    $errors[] = 'Bağlantı hatası: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kurulum</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: Inter, sans-serif; background: #f5f5f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
    .card { background: white; border-radius: 16px; padding: 40px; max-width: 560px; width: 100%; box-shadow: 0 4px 20px rgba(0,0,0,.1); }
    h1 { font-size: 1.5rem; font-weight: 800; margin-bottom: 8px; }
    .ok { background: #e8f5e9; color: #2e7d32; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-weight: 600; }
    .err { background: #ffebee; color: #c62828; padding: 12px 16px; border-radius: 8px; margin-bottom: 8px; font-size: .85rem; }
    .log { background: #f5f5f5; border-radius: 8px; padding: 12px 16px; font-size: .78rem; color: #555; max-height: 200px; overflow-y: auto; margin-top: 12px; }
    a.btn { display: inline-block; margin-top: 20px; background: #d32f2f; color: white; padding: 12px 28px; border-radius: 10px; text-decoration: none; font-weight: 700; }
  </style>
</head>
<body>
<div class="card">
  <h1>🍽️ Restoran Menü Kurulumu</h1>
  <?php if ($status === 'success'): ?>
  <div class="ok">✅ Veritabanı başarıyla kuruldu!</div>
  <?php else: ?>
  <div class="err">❌ Kurulum başarısız. Aşağıdaki hataları kontrol edin.</div>
  <?php endif; ?>

  <?php foreach ($errors as $e): ?>
  <div class="err"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <div class="log">
    <?php foreach ($done as $d): ?>
    <div>✓ <?= htmlspecialchars($d) ?></div>
    <?php endforeach; ?>
  </div>

  <?php if ($status === 'success'): ?>
  <a href="index.php" class="btn">Siteye Git →</a>
  <a href="admin/login.php" class="btn" style="margin-left:8px;background:#1a1a2e">Admin Panel →</a>
  <p style="margin-top:16px;font-size:.8rem;color:#999">⚠️ Kurulum tamamlandıktan sonra bu dosyayı silin: <code>setup.php</code></p>
  <?php endif; ?>
</div>
</body>
</html>
