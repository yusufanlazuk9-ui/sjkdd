<?php
require_once 'includes/auth.php';

$msg = '';
$error = '';

// Upload helper
function handleUpload($field) {
    if (empty($_FILES[$field]['name'])) return null;
    $file = $_FILES[$field];
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed)) return null;
    if ($file['size'] > 2 * 1024 * 1024) return null;
    $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = bin2hex(random_bytes(8)) . '.' . strtolower($ext);
    $dest = __DIR__ . '/../assets/img/' . $name;
    if (move_uploaded_file($file['tmp_name'], $dest)) return $name;
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $cat_id  = (int)($_POST['category_id'] ?? 0);
        $name    = trim($_POST['name'] ?? '');
        $desc    = trim($_POST['description'] ?? '');
        $price   = (float)str_replace(',', '.', $_POST['price'] ?? 0);
        $popular = (int)($_POST['is_popular'] ?? 0);
        $active  = (int)($_POST['is_active'] ?? 1);
        $order   = (int)($_POST['sort_order'] ?? 0);
        $image   = handleUpload('image');

        if (!$cat_id || !$name || $price <= 0) {
            $error = 'Kategori, ürün adı ve fiyat zorunludur.';
        } else {
            db()->prepare('INSERT INTO products (category_id, name, description, price, image, is_popular, is_active, sort_order) VALUES (?,?,?,?,?,?,?,?)')->execute([$cat_id, $name, $desc, $price, $image, $popular, $active, $order]);
            $msg = 'Ürün eklendi.';
        }
    }

    if ($action === 'edit') {
        $id      = (int)($_POST['id'] ?? 0);
        $cat_id  = (int)($_POST['category_id'] ?? 0);
        $name    = trim($_POST['name'] ?? '');
        $desc    = trim($_POST['description'] ?? '');
        $price   = (float)str_replace(',', '.', $_POST['price'] ?? 0);
        $popular = (int)($_POST['is_popular'] ?? 0);
        $active  = (int)($_POST['is_active'] ?? 1);
        $order   = (int)($_POST['sort_order'] ?? 0);
        $image   = handleUpload('image');

        if ($id && $cat_id && $name && $price > 0) {
            if ($image) {
                db()->prepare('UPDATE products SET category_id=?,name=?,description=?,price=?,image=?,is_popular=?,is_active=?,sort_order=? WHERE id=?')->execute([$cat_id,$name,$desc,$price,$image,$popular,$active,$order,$id]);
            } else {
                db()->prepare('UPDATE products SET category_id=?,name=?,description=?,price=?,is_popular=?,is_active=?,sort_order=? WHERE id=?')->execute([$cat_id,$name,$desc,$price,$popular,$active,$order,$id]);
            }
            $msg = 'Ürün güncellendi.';
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            db()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
            $msg = 'Ürün silindi.';
        }
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            db()->prepare('UPDATE products SET is_active = 1 - is_active WHERE id = ?')->execute([$id]);
            $msg = 'Ürün durumu güncellendi.';
        }
    }
}

$cats = db()->query('SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC')->fetchAll();

$filter_cat = (int)($_GET['cat'] ?? 0);
if ($filter_cat) {
    $stmt = db()->prepare('SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON c.id = p.category_id WHERE p.category_id = ? ORDER BY p.sort_order ASC, p.id ASC');
    $stmt->execute([$filter_cat]);
    $products = $stmt->fetchAll();
} else {
    $products = db()->query('SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON c.id = p.category_id ORDER BY c.sort_order ASC, p.sort_order ASC, p.id ASC')->fetchAll();
}

$edit_id = (int)($_GET['edit'] ?? 0);
$edit_prod = null;
if ($edit_id) {
    $stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$edit_id]);
    $edit_prod = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ürünler - Admin</title>
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
    <?php adminNav('Ürünler') ?>
  </aside>

  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button class="admin-mobile-toggle" onclick="document.getElementById('admin-sidebar').classList.toggle('open')">☰</button>
        <h1>Ürünler</h1>
      </div>
    </div>

    <div class="admin-content">
      <?php if ($msg): ?><div style="background:var(--success-light);color:var(--success);padding:12px 16px;border-radius:8px;margin-bottom:16px;font-weight:600">✅ <?= sanitize($msg) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="error-msg" style="margin-bottom:16px">⚠️ <?= sanitize($error) ?></div><?php endif; ?>

      <!-- ADD/EDIT FORM -->
      <div class="admin-card" style="margin-bottom:24px">
        <div class="admin-card-header">
          <h2><?= $edit_prod ? 'Ürün Düzenle' : 'Yeni Ürün Ekle' ?></h2>
          <?php if ($edit_prod): ?><a href="products.php" class="btn btn-sm btn-outline">İptal</a><?php endif; ?>
        </div>
        <div class="admin-card-body">
          <form method="POST" enctype="multipart/form-data" class="admin-form">
            <input type="hidden" name="action" value="<?= $edit_prod ? 'edit' : 'add' ?>">
            <?php if ($edit_prod): ?><input type="hidden" name="id" value="<?= $edit_prod['id'] ?>"><?php endif; ?>
            <div class="form-row">
              <div class="form-group">
                <label>Kategori *</label>
                <select name="category_id" class="form-control" required>
                  <option value="">Kategori seçin</option>
                  <?php foreach ($cats as $c): ?>
                  <option value="<?= $c['id'] ?>" <?= (($edit_prod['category_id'] ?? 0) == $c['id']) ? 'selected' : '' ?>><?= sanitize($c['icon']) ?> <?= sanitize($c['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Ürün Adı *</label>
                <input type="text" name="name" class="form-control" placeholder="Ürün adı" value="<?= sanitize($edit_prod['name'] ?? '') ?>" required>
              </div>
            </div>
            <div class="form-group">
              <label>Açıklama</label>
              <textarea name="description" class="form-control" rows="2" placeholder="Kısa açıklama..."><?= sanitize($edit_prod['description'] ?? '') ?></textarea>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Fiyat (₺) *</label>
                <input type="text" name="price" class="form-control" placeholder="0.00" value="<?= $edit_prod ? number_format($edit_prod['price'], 2, '.', '') : '' ?>" required>
              </div>
              <div class="form-group">
                <label>Sıralama</label>
                <input type="number" name="sort_order" class="form-control" value="<?= (int)($edit_prod['sort_order'] ?? 0) ?>" min="0">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Popüler Ürün</label>
                <select name="is_popular" class="form-control">
                  <option value="0" <?= (($edit_prod['is_popular'] ?? 0) == 0 ? 'selected' : '') ?>>Hayır</option>
                  <option value="1" <?= (($edit_prod['is_popular'] ?? 0) == 1 ? 'selected' : '') ?>>Evet ⭐</option>
                </select>
              </div>
              <div class="form-group">
                <label>Durum</label>
                <select name="is_active" class="form-control">
                  <option value="1" <?= (($edit_prod['is_active'] ?? 1) == 1 ? 'selected' : '') ?>>Aktif</option>
                  <option value="0" <?= (($edit_prod['is_active'] ?? 1) == 0 ? 'selected' : '') ?>>Pasif</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label>Ürün Fotoğrafı <?= $edit_prod ? '(değiştirmek için seçin)' : '' ?></label>
              <input type="file" name="image" class="form-control" accept="image/*">
              <?php if ($edit_prod && $edit_prod['image']): ?>
              <div style="margin-top:8px">
                <img src="../assets/img/<?= sanitize($edit_prod['image']) ?>" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid var(--gray-200)">
              </div>
              <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary" style="width:auto;padding:10px 24px">
              <?= $edit_prod ? 'Güncelle' : 'Ürün Ekle' ?>
            </button>
          </form>
        </div>
      </div>

      <!-- FILTER -->
      <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
        <a href="products.php" class="btn btn-sm <?= !$filter_cat ? 'btn-primary' : 'btn-outline' ?>">Tümü (<?= count($products) ?>)</a>
        <?php foreach ($cats as $c): ?>
        <a href="?cat=<?= $c['id'] ?>" class="btn btn-sm <?= $filter_cat == $c['id'] ? 'btn-primary' : 'btn-outline' ?>"><?= sanitize($c['icon']) ?> <?= sanitize($c['name']) ?></a>
        <?php endforeach; ?>
      </div>

      <!-- PRODUCT LIST -->
      <div class="admin-card">
        <div class="admin-card-header"><h2>Ürün Listesi (<?= count($products) ?>)</h2></div>
        <div style="overflow-x:auto">
          <table class="data-table">
            <thead>
              <tr><th>Foto</th><th>Ürün</th><th>Kategori</th><th>Fiyat</th><th>Popüler</th><th>Durum</th><th>İşlem</th></tr>
            </thead>
            <tbody>
              <?php foreach ($products as $p): ?>
              <tr>
                <td>
                  <?php if ($p['image']): ?>
                  <img src="../assets/img/<?= sanitize($p['image']) ?>" style="width:48px;height:48px;object-fit:cover;border-radius:6px">
                  <?php else: ?>
                  <div style="width:48px;height:48px;background:var(--gray-100);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:1.2rem">🍽️</div>
                  <?php endif; ?>
                </td>
                <td>
                  <strong><?= sanitize($p['name']) ?></strong>
                  <?php if ($p['description']): ?>
                  <div style="font-size:0.78rem;color:var(--gray-400);margin-top:2px;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= sanitize($p['description']) ?></div>
                  <?php endif; ?>
                </td>
                <td style="font-size:0.85rem"><?= sanitize($p['cat_name']) ?></td>
                <td><strong><?= number_format($p['price'], 2) ?> ₺</strong></td>
                <td><?= $p['is_popular'] ? '<span class="badge badge-ready">⭐ Evet</span>' : '<span style="color:var(--gray-400)">—</span>' ?></td>
                <td><span class="badge <?= $p['is_active'] ? 'badge-ready' : 'badge-completed' ?>"><?= $p['is_active'] ? 'Aktif' : 'Pasif' ?></span></td>
                <td>
                  <div class="action-btns">
                    <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-info">Düzenle</a>
                    <form method="POST" style="display:inline">
                      <input type="hidden" name="action" value="toggle">
                      <input type="hidden" name="id" value="<?= $p['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline"><?= $p['is_active'] ? 'Pasif' : 'Aktif' ?></button>
                    </form>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Bu ürünü silmek istediğinizden emin misiniz?')">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= $p['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($products)): ?>
              <tr><td colspan="7" style="text-align:center;color:var(--gray-400);padding:24px">Ürün bulunamadı</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>
</body>
</html>
