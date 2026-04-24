<?php
require_once 'includes/auth.php';

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name  = trim($_POST['name'] ?? '');
        $icon  = trim($_POST['icon'] ?? '🍽️');
        $order = (int)($_POST['sort_order'] ?? 0);
        if (!$name) { $error = 'Kategori adı zorunludur.'; }
        else {
            db()->prepare('INSERT INTO categories (name, icon, sort_order) VALUES (?,?,?)')->execute([$name, $icon, $order]);
            $msg = 'Kategori eklendi.';
        }
    }

    if ($action === 'edit') {
        $id    = (int)($_POST['id'] ?? 0);
        $name  = trim($_POST['name'] ?? '');
        $icon  = trim($_POST['icon'] ?? '🍽️');
        $order = (int)($_POST['sort_order'] ?? 0);
        $active = (int)($_POST['is_active'] ?? 1);
        if ($id && $name) {
            db()->prepare('UPDATE categories SET name=?, icon=?, sort_order=?, is_active=? WHERE id=?')->execute([$name, $icon, $order, $active, $id]);
            $msg = 'Kategori güncellendi.';
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            $msg = 'Kategori silindi.';
        }
    }
}

$cats = db()->query("SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as product_count FROM categories c ORDER BY c.sort_order ASC, c.id ASC")->fetchAll();
$edit_id = (int)($_GET['edit'] ?? 0);
$edit_cat = null;
if ($edit_id) {
    foreach ($cats as $c) { if ($c['id'] == $edit_id) { $edit_cat = $c; break; } }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kategoriler - Admin</title>
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
    <?php adminNav('Kategoriler') ?>
  </aside>

  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button class="admin-mobile-toggle" onclick="document.getElementById('admin-sidebar').classList.toggle('open')">☰</button>
        <h1>Kategoriler</h1>
      </div>
    </div>

    <div class="admin-content">
      <?php if ($msg): ?><div style="background:var(--success-light);color:var(--success);padding:12px 16px;border-radius:8px;margin-bottom:16px;font-weight:600">✅ <?= sanitize($msg) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="error-msg" style="margin-bottom:16px">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <!-- ADD/EDIT FORM -->
        <div class="admin-card">
          <div class="admin-card-header">
            <h2><?= $edit_cat ? 'Kategori Düzenle' : 'Yeni Kategori Ekle' ?></h2>
            <?php if ($edit_cat): ?><a href="categories.php" class="btn btn-sm btn-outline">İptal</a><?php endif; ?>
          </div>
          <div class="admin-card-body">
            <form method="POST" class="admin-form">
              <input type="hidden" name="action" value="<?= $edit_cat ? 'edit' : 'add' ?>">
              <?php if ($edit_cat): ?><input type="hidden" name="id" value="<?= $edit_cat['id'] ?>"><?php endif; ?>
              <div class="form-group">
                <label>Kategori Adı *</label>
                <input type="text" name="name" class="form-control" placeholder="Örn: Ana Yemekler" value="<?= sanitize($edit_cat['name'] ?? '') ?>" required>
              </div>
              <div class="form-group">
                <label>Emoji / İkon</label>
                <input type="text" name="icon" class="form-control" placeholder="Emoji (Örn: 🍕)" value="<?= sanitize($edit_cat['icon'] ?? '🍽️') ?>">
              </div>
              <div class="form-group">
                <label>Sıralama</label>
                <input type="number" name="sort_order" class="form-control" value="<?= (int)($edit_cat['sort_order'] ?? 0) ?>" min="0">
              </div>
              <?php if ($edit_cat): ?>
              <div class="form-group">
                <label>Durum</label>
                <select name="is_active" class="form-control">
                  <option value="1" <?= ($edit_cat['is_active'] == 1 ? 'selected' : '') ?>>Aktif</option>
                  <option value="0" <?= ($edit_cat['is_active'] == 0 ? 'selected' : '') ?>>Pasif</option>
                </select>
              </div>
              <?php endif; ?>
              <button type="submit" class="btn btn-primary" style="width:auto;padding:10px 24px">
                <?= $edit_cat ? 'Güncelle' : 'Kategori Ekle' ?>
              </button>
            </form>
          </div>
        </div>

        <!-- LIST -->
        <div class="admin-card">
          <div class="admin-card-header"><h2>Mevcut Kategoriler (<?= count($cats) ?>)</h2></div>
          <div style="overflow:auto;max-height:500px">
            <table class="data-table">
              <thead><tr><th>İkon</th><th>Ad</th><th>Ürünler</th><th>Durum</th><th>İşlem</th></tr></thead>
              <tbody>
                <?php foreach ($cats as $c): ?>
                <tr>
                  <td style="font-size:1.4rem"><?= sanitize($c['icon']) ?></td>
                  <td><strong><?= sanitize($c['name']) ?></strong></td>
                  <td><span class="badge badge-preparing"><?= $c['product_count'] ?></span></td>
                  <td><span class="badge <?= $c['is_active'] ? 'badge-ready' : 'badge-completed' ?>"><?= $c['is_active'] ? 'Aktif' : 'Pasif' ?></span></td>
                  <td>
                    <div class="action-btns">
                      <a href="?edit=<?= $c['id'] ?>" class="btn btn-sm btn-info">Düzenle</a>
                      <form method="POST" style="display:inline" onsubmit="return confirm('Bu kategoriyi silmek istediğinizden emin misiniz?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
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
      </div>
    </div>
  </main>
</div>
</body>
</html>
