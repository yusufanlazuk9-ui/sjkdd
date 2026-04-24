<?php
require_once 'includes/config.php';

if (empty($_SESSION['table_id'])) {
    header('Location: index.php');
    exit;
}

$order_id = (int)($_GET['order_id'] ?? 0);
if (!$order_id) {
    header('Location: menu.php');
    exit;
}

$stmt = db()->prepare('SELECT o.*, t.table_number FROM orders o JOIN tables t ON t.id = o.table_id WHERE o.id = ? AND o.table_id = ?');
$stmt->execute([$order_id, $_SESSION['table_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: menu.php');
    exit;
}

$items_stmt = db()->prepare('SELECT oi.*, p.name as product_name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?');
$items_stmt->execute([$order_id]);
$order_items = $items_stmt->fetchAll();

$status_map = [
    'pending'   => ['label' => 'Bekliyor', 'icon' => '⏳'],
    'preparing' => ['label' => 'Hazırlanıyor', 'icon' => '👨‍🍳'],
    'ready'     => ['label' => 'Hazır', 'icon' => '✅'],
    'completed' => ['label' => 'Tamamlandı', 'icon' => '🎉'],
];

$steps = ['pending', 'preparing', 'ready'];
$current_status = $order['status'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sipariş Durumu #<?= $order_id ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="order-track">
  <div class="track-hero">
    <div class="check-icon">
      <?= $current_status === 'ready' ? '✅' : ($current_status === 'completed' ? '🎉' : '⏳') ?>
    </div>
    <h1>
      <?php if ($current_status === 'pending'): ?>Siparişiniz Alındı!
      <?php elseif ($current_status === 'preparing'): ?>Siparişiniz Hazırlanıyor...
      <?php elseif ($current_status === 'ready'): ?>Siparişiniz Hazır!
      <?php else: ?>Siparişiniz Tamamlandı
      <?php endif; ?>
    </h1>
    <p>Masa <?= sanitize($order['table_number']) ?> • Sipariş #<?= $order_id ?></p>
  </div>

  <!-- STATUS STEPS -->
  <div class="track-card">
    <h3>SİPARİŞ DURUMU</h3>
    <div class="status-steps">
      <?php
      $statuses = [
          ['key' => 'pending',   'title' => 'Sipariş Alındı',   'sub' => 'Siparişiniz mutfağa iletildi.'],
          ['key' => 'preparing', 'title' => 'Hazırlanıyor',     'sub' => 'Şeflerimiz siparişiniz üzerinde çalışıyor.'],
          ['key' => 'ready',     'title' => 'Hazır!',           'sub' => 'Siparişiniz masanıza geliyor.'],
      ];
      $reached = false;
      foreach ($statuses as $step):
          $isDone = false;
          $isActive = ($step['key'] === $current_status);
          if ($current_status === 'pending' && $step['key'] === 'pending') { $isDone = false; $isActive = true; }
          elseif ($current_status === 'preparing' && $step['key'] === 'pending') $isDone = true;
          elseif ($current_status === 'preparing' && $step['key'] === 'preparing') { $isDone = false; $isActive = true; }
          elseif ($current_status === 'ready' || $current_status === 'completed') {
              if (in_array($step['key'], ['pending', 'preparing'])) $isDone = true;
              if ($step['key'] === 'ready') { $isDone = false; $isActive = true; }
          }
          $class = $isDone ? 'done' : ($isActive ? 'active' : '');
      ?>
      <div class="status-step <?= $class ?>">
        <div class="step-dot">
          <?= $isDone ? '✓' : ($isActive ? '●' : '○') ?>
        </div>
        <div class="step-info">
          <div class="step-title"><?= $step['title'] ?></div>
          <div class="step-sub"><?= $step['sub'] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ORDER ITEMS -->
  <div class="track-card">
    <h3>SİPARİŞ DETAYI</h3>
    <div class="order-items-list">
      <?php foreach ($order_items as $item): ?>
      <div class="order-item-row">
        <div>
          <span class="order-item-name"><?= sanitize($item['product_name']) ?></span>
          <span class="order-item-qty">×<?= $item['quantity'] ?></span>
        </div>
        <span class="order-item-price"><?= number_format($item['unit_price'] * $item['quantity'], 2) ?> ₺</span>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="order-total-bar">
      <span>Toplam</span>
      <span><?= number_format($order['total_amount'], 2) ?> ₺</span>
    </div>
    <?php if ($order['notes']): ?>
    <div style="margin-top:12px;padding:10px 12px;background:var(--gray-100);border-radius:8px;font-size:0.85rem;color:var(--gray-600);">
      📝 <?= sanitize($order['notes']) ?>
    </div>
    <?php endif; ?>
  </div>

  <div style="padding:0 16px;">
    <a href="menu.php" class="btn btn-primary" style="text-decoration:none">
      + Yeni Sipariş Ver
    </a>
  </div>
</div>

<div class="toast-container" id="toast-container"></div>
<script src="assets/js/menu.js"></script>
<script>
// Auto-refresh every 20 seconds to check status
<?php if (!in_array($current_status, ['ready', 'completed'])): ?>
setTimeout(() => location.reload(), 20000);
<?php endif; ?>
</script>
</body>
</html>
