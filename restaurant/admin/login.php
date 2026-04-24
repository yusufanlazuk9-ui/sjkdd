<?php
require_once '../includes/config.php';

if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === ADMIN_USER && password_verify($password, ADMIN_PASS)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $username;
        header('Location: index.php'); exit;
    } else {
        $error = 'Kullanıcı adı veya şifre hatalı.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Giriş</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-login">
  <div class="admin-login-card">
    <div style="width:52px;height:52px;background:linear-gradient(135deg,var(--primary),var(--accent));border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:20px;">🔐</div>
    <h1>Admin Paneli</h1>
    <p>Yönetim paneline giriş yapın</p>

    <form method="POST" autocomplete="off">
      <div class="form-group">
        <label>Kullanıcı Adı</label>
        <input type="text" name="username" class="form-control <?= $error ? 'error' : '' ?>" placeholder="admin" value="<?= sanitize($_POST['username'] ?? '') ?>" autofocus>
      </div>
      <div class="form-group">
        <label>Şifre</label>
        <input type="password" name="password" class="form-control <?= $error ? 'error' : '' ?>" placeholder="••••••••">
      </div>
      <?php if ($error): ?>
        <div class="error-msg">⚠️ <?= sanitize($error) ?></div>
        <br>
      <?php endif; ?>
      <button type="submit" class="btn btn-primary">Giriş Yap</button>
    </form>
    <p style="text-align:center;margin-top:20px;font-size:0.8rem;color:var(--gray-400)">Varsayılan: admin / password</p>
  </div>
</div>
</body>
</html>
