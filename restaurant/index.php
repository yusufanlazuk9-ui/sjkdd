<?php
require_once 'includes/config.php';

// Direct QR code link: ?table_direct=MASA01
if (!empty($_GET['table_direct'])) {
    $code = strtoupper(trim($_GET['table_direct']));
    $stmt = db()->prepare('SELECT id, table_number FROM tables WHERE table_code = ? AND is_active = 1');
    $stmt->execute([$code]);
    $table = $stmt->fetch();
    if ($table) {
        $_SESSION['table_id'] = $table['id'];
        $_SESSION['table_number'] = $table['table_number'];
        $_SESSION['table_code'] = $code;
        $_SESSION['session_token'] = bin2hex(random_bytes(16));
        header('Location: menu.php');
        exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['table_code'] ?? ''));
    if (empty($code)) {
        $error = 'Lütfen masa kodunuzu giriniz.';
    } else {
        $stmt = db()->prepare('SELECT id, table_number FROM tables WHERE table_code = ? AND is_active = 1');
        $stmt->execute([$code]);
        $table = $stmt->fetch();
        if ($table) {
            $_SESSION['table_id'] = $table['id'];
            $_SESSION['table_number'] = $table['table_number'];
            $_SESSION['table_code'] = $code;
            $_SESSION['session_token'] = bin2hex(random_bytes(16));
            header('Location: menu.php');
            exit;
        } else {
            $error = 'Geçersiz masa kodu. Lütfen tekrar deneyiniz.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hoş Geldiniz - Dijital Menü</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="entry-screen">
  <div class="entry-card">
    <div class="entry-logo">🍽️</div>
    <h1>Hoş Geldiniz</h1>
    <p>Menüye erişmek için lütfen masanızdaki QR kodu okutun veya masa kodunuzu girin.</p>

    <form method="POST" autocomplete="off">
      <div class="form-group">
        <label for="table_code">Masa Kodu</label>
        <input
          type="text"
          id="table_code"
          name="table_code"
          class="form-control <?= $error ? 'error' : '' ?>"
          placeholder="Örn: MASA01"
          value="<?= sanitize($_POST['table_code'] ?? '') ?>"
          autocomplete="off"
          autofocus
        >
      </div>
      <?php if ($error): ?>
        <div class="error-msg">⚠️ <?= sanitize($error) ?></div>
      <?php endif; ?>
      <br>
      <button type="submit" class="btn btn-primary">
        Menüyü Görüntüle
      </button>
    </form>
  </div>
</div>
</body>
</html>
