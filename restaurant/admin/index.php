<?php
require_once 'includes/auth.php';

// Stats
$total_orders   = db()->query("SELECT COUNT(*) FROM orders WHERE status != 'completed'")->fetchColumn();
$pending_orders = db()->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$preparing      = db()->query("SELECT COUNT(*) FROM orders WHERE status = 'preparing'")->fetchColumn();
$ready          = db()->query("SELECT COUNT(*) FROM orders WHERE status = 'ready'")->fetchColumn();
$total_tables   = db()->query("SELECT COUNT(*) FROM tables WHERE is_active = 1")->fetchColumn();
$total_products = db()->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
$today_revenue  = db()->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'completed'")->fetchColumn();

// Recent orders
$recent = db()->query("SELECT o.*, t.table_number FROM orders o JOIN tables t ON t.id = o.table_id WHERE o.status != 'completed' ORDER BY o.created_at DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="admin-body">
<div style="display:flex">
  <!-- SIDEBAR -->
  <aside class="admin-sidebar" id="admin-sidebar">
    <div class="admin-sidebar-header">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px">
        <span style="font-size:22px">🍽️</span>
        <div>
          <h2>Admin Panel</h2>
          <span>Restoran Yönetimi</span>
        </div>
      </div>
    </div>
    <?php adminNav('Dashboard') ?>
  </aside>

  <!-- MAIN -->
  <main class="admin-main">
    <div class="admin-topbar">
      <div style="display:flex;align-items:center;gap:12px">
        <button class="admin-mobile-toggle" onclick="document.getElementById('admin-sidebar').classList.toggle('open')">☰</button>
        <h1>Dashboard</h1>
      </div>
      <span style="font-size:0.85rem;color:var(--gray-500)"><?= date('d.m.Y H:i') ?></span>
    </div>

    <div class="admin-content">
      <!-- STATS -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">📋</div>
          <div class="stat-info">
            <h3><?= $total_orders ?></h3>
            <p>Aktif Sipariş</p>
          </div>
        </div>
        <div class="stat-card stat-orange">
          <div class="stat-icon">⏳</div>
          <div class="stat-info">
            <h3><?= $pending_orders ?></h3>
            <p>Bekleyen</p>
          </div>
        </div>
        <div class="stat-card stat-blue">
          <div class="stat-icon">👨‍🍳</div>
          <div class="stat-info">
            <h3><?= $preparing ?></h3>
            <p>Hazırlanıyor</p>
          </div>
        </div>
        <div class="stat-card stat-green">
          <div class="stat-icon">✅</div>
          <div class="stat-info">
            <h3><?= $ready ?></h3>
            <p>Hazır</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">🪑</div>
          <div class="stat-info">
            <h3><?= $total_tables ?></h3>
            <p>Aktif Masa</p>
          </div>
        </div>
        <div class="stat-card stat-green">
          <div class="stat-icon">💰</div>
          <div class="stat-info">
            <h3><?= number_format($today_revenue, 0) ?> ₺</h3>
            <p>Bugünkü Ciro</p>
          </div>
        </div>
      </div>

      <!-- RECENT ORDERS -->
      <div class="admin-card">
        <div class="admin-card-header">
          <h2>Aktif Siparişler</h2>
          <a href="orders.php" class="btn btn-outline btn-sm">Tümünü Gör</a>
        </div>
        <div style="overflow-x:auto">
          <table class="data-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Masa</th>
                <th>Tutar</th>
                <th>Durum</th>
                <th>Tarih</th>
                <th>İşlem</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent as $o): ?>
              <tr>
                <td><?= $o['id'] ?></td>
                <td><strong>Masa <?= sanitize($o['table_number']) ?></strong></td>
                <td><?= number_format($o['total_amount'], 2) ?> ₺</td>
                <td><span class="badge badge-<?= $o['status'] ?>"><?= ['pending'=>'Bekliyor','preparing'=>'Hazırlanıyor','ready'=>'Hazır'][$o['status']] ?? $o['status'] ?></span></td>
                <td style="font-size:0.8rem;color:var(--gray-500)"><?= date('H:i', strtotime($o['created_at'])) ?></td>
                <td><a href="orders.php" class="btn btn-info btn-sm">Yönet</a></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($recent)): ?>
              <tr><td colspan="6" style="text-align:center;color:var(--gray-400);padding:24px">Aktif sipariş yok</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>
<script>
setInterval(() => location.reload(), 30000);
</script>
</body>
</html>
