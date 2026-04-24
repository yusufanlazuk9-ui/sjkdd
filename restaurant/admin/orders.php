<?php
require_once 'includes/auth.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $status   = $_POST['status'] ?? '';
    $allowed  = ['pending', 'preparing', 'ready', 'completed'];
    if ($order_id && in_array($status, $allowed)) {
        db()->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $order_id]);
    }
    header('Location: orders.php'); exit;
}

$filter = $_GET['filter'] ?? 'active';
if ($filter === 'all') {
    $orders = db()->query("SELECT o.*, t.table_number FROM orders o JOIN tables t ON t.id = o.table_id ORDER BY o.created_at DESC LIMIT 200")->fetchAll();
} else {
    $orders = db()->query("SELECT o.*, t.table_number FROM orders o JOIN tables t ON t.id = o.table_id WHERE o.status != 'completed' ORDER BY o.created_at DESC")->fetchAll();
}

// Load items for each order
$order_items_map = [];
if ($orders) {
    $ids = array_column($orders, 'id');
    $in = implode(',', $ids);
    $items_all = db()->query("SELECT oi.order_id, oi.quantity, p.name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id IN ($in)")->fetchAll();
    foreach ($items_all as $it) {
        $order_items_map[$it['order_id']][] = $it;
    }
}

$status_labels = ['pending'=>'Bekliyor','preparing'=>'Hazırlanıyor','ready'=>'Hazır','completed'=>'Tamamlandı'];
$status_next   = ['pending'=>'preparing','preparing'=>'ready','ready'=>'completed'];
$status_next_label = ['pending'=>'Hazırlanıyor','preparing'=>'Hazır Olarak İşaretle','ready'=>'Tamamla'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Siparişler - Admin</title>
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
    <?php adminNav('Siparişler') ?>
  </aside>

  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button class="admin-mobile-toggle" onclick="document.getElementById('admin-sidebar').classList.toggle('open')">☰</button>
        <h1>Siparişler</h1>
      </div>
      <div style="display:flex;gap:8px">
        <a href="?filter=active" class="btn btn-sm <?= $filter==='active'?'btn-primary':'btn-outline' ?>">Aktif</a>
        <a href="?filter=all"    class="btn btn-sm <?= $filter==='all'?'btn-primary':'btn-outline' ?>">Tümü</a>
      </div>
    </div>

    <div class="admin-content">
      <!-- KANBAN -->
      <?php if ($filter === 'active'): ?>
      <div class="kanban-board">
        <?php
        $kanban_statuses = [
            'pending'   => ['label' => '⏳ Bekliyor',      'col' => 'pending'],
            'preparing' => ['label' => '👨‍🍳 Hazırlanıyor', 'col' => 'preparing'],
            'ready'     => ['label' => '✅ Hazır',         'col' => 'ready'],
        ];
        foreach ($kanban_statuses as $skey => $sinfo):
            $col_orders = array_filter($orders, fn($o) => $o['status'] === $skey);
        ?>
        <div class="kanban-col">
          <div class="kanban-col-header">
            <h3><?= $sinfo['label'] ?></h3>
            <span class="kanban-count"><?= count($col_orders) ?></span>
          </div>
          <?php foreach ($col_orders as $o): ?>
          <div class="order-card status-<?= $o['status'] ?>">
            <div class="order-card-top">
              <span class="order-card-table">Masa <?= sanitize($o['table_number']) ?></span>
              <span class="order-card-time"><?= date('H:i', strtotime($o['created_at'])) ?></span>
            </div>
            <div class="order-card-items">
              <?php foreach (($order_items_map[$o['id']] ?? []) as $it): ?>
              <div>• <?= $it['quantity'] ?>× <?= sanitize($it['name']) ?></div>
              <?php endforeach; ?>
              <?php if ($o['notes']): ?>
              <div style="margin-top:4px;font-style:italic;color:var(--gray-500)">📝 <?= sanitize($o['notes']) ?></div>
              <?php endif; ?>
            </div>
            <div class="order-card-footer">
              <span class="order-card-total"><?= number_format($o['total_amount'],2) ?> ₺</span>
              <div class="action-btns">
                <?php if (isset($status_next[$o['status']])): ?>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                  <input type="hidden" name="status" value="<?= $status_next[$o['status']] ?>">
                  <button type="submit" name="update_status" class="btn btn-sm btn-success" style="font-size:0.75rem">
                    <?= $status_next_label[$o['status']] ?> →
                  </button>
                </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if (empty($col_orders)): ?>
          <div style="text-align:center;padding:20px;color:var(--gray-400);font-size:0.85rem">Sipariş yok</div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>

      <?php else: ?>
      <!-- TABLE VIEW -->
      <div class="admin-card">
        <div class="admin-card-header"><h2>Tüm Siparişler</h2></div>
        <div style="overflow-x:auto">
          <table class="data-table">
            <thead>
              <tr><th>#</th><th>Masa</th><th>Ürünler</th><th>Tutar</th><th>Durum</th><th>Tarih</th><th>İşlem</th></tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $o): ?>
              <tr>
                <td><?= $o['id'] ?></td>
                <td><strong>Masa <?= sanitize($o['table_number']) ?></strong></td>
                <td style="font-size:0.8rem;max-width:200px">
                  <?php foreach (($order_items_map[$o['id']] ?? []) as $it): ?>
                  <div><?= $it['quantity'] ?>× <?= sanitize($it['name']) ?></div>
                  <?php endforeach; ?>
                </td>
                <td><?= number_format($o['total_amount'],2) ?> ₺</td>
                <td><span class="badge badge-<?= $o['status'] ?>"><?= $status_labels[$o['status']] ?? $o['status'] ?></span></td>
                <td style="font-size:0.8rem;color:var(--gray-500)"><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
                <td>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                    <select name="status" class="form-control" style="display:inline;width:auto;padding:4px 8px;font-size:0.8rem">
                      <?php foreach ($status_labels as $sv => $sl): ?>
                      <option value="<?= $sv ?>" <?= $o['status']===$sv?'selected':'' ?>><?= $sl ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button type="submit" name="update_status" class="btn btn-sm btn-success">Güncelle</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($orders)): ?>
              <tr><td colspan="7" style="text-align:center;color:var(--gray-400);padding:24px">Sipariş bulunamadı</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </main>
</div>
<script>
if (document.querySelector('[data-filter="active"]') || window.location.search.indexOf('filter=all') === -1) {
    setTimeout(() => location.reload(), 15000);
}
</script>
</body>
</html>
