<?php
require_once 'includes/auth.php';

$msg = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $num  = strtoupper(trim($_POST['table_number'] ?? ''));
        $code = strtoupper(trim($_POST['table_code'] ?? ''));
        $cap  = max(1, (int)($_POST['capacity'] ?? 4));

        if (!$num || !$code) {
            $error = 'Masa numarası ve kodu zorunludur.';
        } else {
            try {
                db()->prepare('INSERT INTO tables (table_number, table_code, capacity) VALUES (?,?,?)')->execute([$num, $code, $cap]);
                $msg = 'Masa başarıyla eklendi.';
            } catch (PDOException $e) {
                $error = 'Bu masa numarası veya kod zaten mevcut.';
            }
        }
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            db()->prepare('UPDATE tables SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
            $msg = 'Masa durumu güncellendi.';
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            db()->prepare('DELETE FROM tables WHERE id = ?')->execute([$id]);
            $msg = 'Masa silindi.';
        }
    }

    if ($action === 'reset') {
        // Close active orders for this table
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            db()->prepare("UPDATE orders SET status = 'completed' WHERE table_id = ? AND status != 'completed'")->execute([$id]);
            $msg = 'Masa sıfırlandı. Tüm siparişler kapatıldı.';
        }
    }
}

$tables = db()->query("SELECT t.*, (SELECT COUNT(*) FROM orders o WHERE o.table_id = t.id AND o.status != 'completed') as active_orders FROM tables t ORDER BY t.table_number ASC")->fetchAll();

$site_url = SITE_URL;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Masalar - Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
<div style="display:flex">
  <aside class="admin-sidebar" id="admin-sidebar">
    <div class="admin-sidebar-header">
      <div style="display:flex;align-items:center;gap:10px">
        <span style="font-size:22px">🍽️</span>
        <div><h2>Admin Panel</h2><span>Restoran Yönetimi</span></div>
      </div>
    </div>
    <?php adminNav('Masalar') ?>
  </aside>

  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button class="admin-mobile-toggle" onclick="document.getElementById('admin-sidebar').classList.toggle('open')">☰</button>
        <h1>Masalar</h1>
      </div>
    </div>

    <div class="admin-content">
      <?php if ($msg): ?><div style="background:var(--success-light);color:var(--success);padding:12px 16px;border-radius:8px;margin-bottom:16px;font-weight:600">✅ <?= sanitize($msg) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="error-msg" style="margin-bottom:16px">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

      <!-- ADD TABLE -->
      <div class="admin-card" style="margin-bottom:24px">
        <div class="admin-card-header"><h2>Yeni Masa Ekle</h2></div>
        <div class="admin-card-body">
          <form method="POST" class="admin-form">
            <input type="hidden" name="action" value="add">
            <div class="form-row">
              <div class="form-group">
                <label>Masa Numarası</label>
                <input type="text" name="table_number" class="form-control" placeholder="Örn: 1, A1, VIP" required>
              </div>
              <div class="form-group">
                <label>Masa Kodu (QR için)</label>
                <input type="text" name="table_code" class="form-control" placeholder="Örn: MASA09" required>
              </div>
              <div class="form-group">
                <label>Kapasite</label>
                <input type="number" name="capacity" class="form-control" value="4" min="1" max="50">
              </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:auto;padding:10px 24px">Masa Ekle</button>
          </form>
        </div>
      </div>

      <!-- TABLE LIST -->
      <div class="admin-card">
        <div class="admin-card-header"><h2>Mevcut Masalar (<?= count($tables) ?>)</h2></div>
        <div style="overflow-x:auto">
          <table class="data-table">
            <thead>
              <tr><th>No</th><th>Masa Kodu</th><th>Kapasite</th><th>Aktif Sipariş</th><th>Durum</th><th>Menü Linki</th><th>İşlemler</th></tr>
            </thead>
            <tbody>
              <?php foreach ($tables as $t): ?>
              <tr>
                <td><strong>Masa <?= sanitize($t['table_number']) ?></strong></td>
                <td><code style="background:var(--gray-100);padding:2px 8px;border-radius:4px;font-size:0.85rem"><?= sanitize($t['table_code']) ?></code></td>
                <td><?= $t['capacity'] ?> kişi</td>
                <td>
                  <?php if ($t['active_orders'] > 0): ?>
                  <span class="badge badge-preparing"><?= $t['active_orders'] ?> aktif</span>
                  <?php else: ?>
                  <span style="color:var(--gray-400);font-size:0.85rem">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge <?= $t['is_active'] ? 'badge-ready' : 'badge-completed' ?>">
                    <?= $t['is_active'] ? 'Aktif' : 'Pasif' ?>
                  </span>
                </td>
                <td>
                  <a href="<?= $site_url ?>/?table_direct=<?= urlencode($t['table_code']) ?>" target="_blank" style="font-size:0.8rem;color:var(--primary)">🔗 Link</a>
                </td>
                <td>
                  <div class="action-btns">
                    <?php if ($t['active_orders'] > 0): ?>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Bu masayı sıfırlamak istediğinizden emin misiniz? Tüm aktif siparişler kapatılacak.')">
                      <input type="hidden" name="action" value="reset">
                      <input type="hidden" name="id" value="<?= $t['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-danger">Sıfırla</button>
                    </form>
                    <?php endif; ?>
                    <form method="POST" style="display:inline">
                      <input type="hidden" name="action" value="toggle">
                      <input type="hidden" name="id" value="<?= $t['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline"><?= $t['is_active'] ? 'Pasif Yap' : 'Aktif Yap' ?></button>
                    </form>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Bu masayı silmek istediğinizden emin misiniz?')">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= $t['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- QR INFO -->
      <div class="admin-card">
        <div class="admin-card-header"><h2>QR Kod Bilgisi</h2></div>
        <div class="admin-card-body">
          <p style="font-size:0.9rem;color:var(--gray-600);margin-bottom:12px">
            Her masa için aşağıdaki URL'yi QR koda dönüştürün ve masaya yerleştirin. Müşteriler bu QR'yi okutunca doğrudan menüye erişecekler.
          </p>
          <div style="background:var(--gray-100);border-radius:8px;padding:12px 16px;font-family:monospace;font-size:0.85rem;color:var(--gray-700)">
            <?= $site_url ?>/?table_direct=<strong>[MASA_KODU]</strong>
          </div>
          <p style="font-size:0.8rem;color:var(--gray-400);margin-top:8px">
            Örnek: <?= $site_url ?>/?table_direct=MASA01
          </p>
        </div>
      </div>
    </div>
  </main>
</div>
</body>
</html>
