<?php
require_once 'includes/config.php';

if (empty($_SESSION['table_id'])) {
    header('Location: index.php');
    exit;
}

$table_id = (int)$_SESSION['table_id'];
$table_number = sanitize($_SESSION['table_number']);
$session_token = sanitize($_SESSION['session_token']);

// Load categories with products
$cats = db()->query('SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
$catIds = array_column($cats, 'id');

$products = [];
if ($catIds) {
    $in = implode(',', array_fill(0, count($catIds), '?'));
    $stmt = db()->prepare("SELECT p.*, c.icon as category_icon FROM products p JOIN categories c ON c.id = p.category_id WHERE p.category_id IN ($in) AND p.is_active = 1 ORDER BY p.sort_order ASC, p.id ASC");
    $stmt->execute($catIds);
    foreach ($stmt->fetchAll() as $p) {
        $products[$p['category_id']][] = $p;
    }
}

// Popular products
$popular = db()->query("SELECT p.*, c.icon as category_icon FROM products p JOIN categories c ON c.id = p.category_id WHERE p.is_popular = 1 AND p.is_active = 1 ORDER BY p.id ASC LIMIT 12")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menü - Masa <?= $table_number ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- HEADER -->
<header class="menu-header">
  <div class="header-top">
    <div class="header-brand">
      <div class="logo-icon">🍽️</div>
      <div>
        <h2>Dijital Menü</h2>
        <span>Lezzet durağınız</span>
      </div>
    </div>
    <div class="header-actions">
      <button class="cart-btn" onclick="openCart()">
        🛒 Sepet
        <span class="cart-badge" id="cart-badge">0</span>
      </button>
    </div>
  </div>

  <!-- CATEGORY TABS -->
  <nav class="category-tabs">
    <div class="tabs-inner">
      <?php foreach ($cats as $cat): ?>
        <?php if (!empty($products[$cat['id']])): ?>
        <button class="tab-btn" data-id="<?= $cat['id'] ?>" onclick="scrollToCategory(<?= $cat['id'] ?>)">
          <?= sanitize($cat['icon']) ?> <?= sanitize($cat['name']) ?>
        </button>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </nav>
</header>

<!-- HERO -->
<div class="menu-hero">
  <h1>Bugün Ne Yemek İstersiniz?</h1>
  <p>Taze malzemeler, eşsiz lezzetler</p>
  <div class="table-badge">
    📍 Masa <?= $table_number ?>
  </div>
</div>

<!-- MENU BODY -->
<div class="menu-body">

  <!-- POPULAR SECTION -->
  <?php if (!empty($popular)): ?>
  <div class="popular-section">
    <div class="section-header">
      <span class="section-icon">⭐</span>
      <h2>Popüler Ürünler</h2>
    </div>
    <div class="popular-scroll">
      <?php foreach ($popular as $p): ?>
      <div class="popular-card" onclick="Modal.open(<?= json_encode($p) ?>)">
        <div class="popular-card-img">
          <?php if ($p['image']): ?>
            <img src="assets/img/<?= sanitize($p['image']) ?>" alt="<?= sanitize($p['name']) ?>" onerror="this.parentElement.innerHTML='<span><?= sanitize($p['category_icon']) ?></span>'">
          <?php else: ?>
            <span><?= sanitize($p['category_icon']) ?></span>
          <?php endif; ?>
        </div>
        <div class="popular-card-body">
          <div class="popular-card-name"><?= sanitize($p['name']) ?></div>
          <div style="display:flex;align-items:center;justify-content:space-between;margin-top:6px">
            <span class="popular-card-price"><?= number_format($p['price'], 2) ?> ₺</span>
            <button class="popular-card-add" onclick="event.stopPropagation();Cart.add(<?= json_encode($p) ?>,1,'');showToast('<?= addslashes(sanitize($p['name'])) ?> eklendi!','success')">+</button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- CATEGORIES + PRODUCTS -->
  <?php foreach ($cats as $cat): ?>
    <?php if (empty($products[$cat['id']])): continue; endif; ?>
    <section class="category-section" id="cat-<?= $cat['id'] ?>">
      <div class="section-header">
        <span class="section-icon"><?= sanitize($cat['icon']) ?></span>
        <h2><?= sanitize($cat['name']) ?></h2>
        <span class="section-count"><?= count($products[$cat['id']]) ?></span>
      </div>
      <div class="products-grid">
        <?php foreach ($products[$cat['id']] as $p): ?>
        <div class="product-card" onclick="Modal.open(<?= json_encode($p) ?>)">
          <div class="product-img-wrap">
            <?php if ($p['image']): ?>
              <img src="assets/img/<?= sanitize($p['image']) ?>" alt="<?= sanitize($p['name']) ?>" onerror="this.onerror=null;this.parentElement.innerHTML='<div class=\'product-img-placeholder\'><?= sanitize($p['category_icon']) ?></div>'">
            <?php else: ?>
              <div class="product-img-placeholder"><?= sanitize($p['category_icon']) ?></div>
            <?php endif; ?>
          </div>
          <div class="product-info">
            <div class="product-name"><?= sanitize($p['name']) ?></div>
            <?php if ($p['description']): ?>
            <div class="product-desc"><?= sanitize($p['description']) ?></div>
            <?php endif; ?>
            <div class="product-price-row">
              <span class="product-price"><?= number_format($p['price'], 2) ?> ₺</span>
              <?php if ($p['is_popular']): ?>
              <span class="popular-tag">⭐ Popüler</span>
              <?php endif; ?>
            </div>
          </div>
          <button class="add-btn" onclick="event.stopPropagation();Cart.add(<?= json_encode($p) ?>,1,'');showToast('<?= addslashes(sanitize($p['name'])) ?> eklendi!','success')">+</button>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>

</div>

<!-- CART OVERLAY + DRAWER -->
<div class="cart-overlay" id="cart-overlay" onclick="closeCart()"></div>
<div class="cart-drawer" id="cart-drawer">
  <div class="cart-handle"></div>
  <div class="cart-header">
    <h3>🛒 Sepetim</h3>
    <button class="cart-close" onclick="closeCart()">✕</button>
  </div>
  <div class="cart-items" id="cart-items"></div>
  <div class="cart-empty" id="cart-empty" style="display:none">
    <div class="cart-empty-icon">🛒</div>
    <p>Sepetiniz boş</p>
  </div>
  <div class="cart-footer" id="cart-footer" style="display:none">
    <textarea class="cart-notes" id="cart-notes" rows="2" placeholder="Sipariş notunuzu yazın... (isteğe bağlı)"></textarea>
    <div class="cart-total-row">
      <span class="cart-total-label">Toplam</span>
      <span class="cart-total-amount" id="cart-total">0.00 ₺</span>
    </div>
    <button class="btn btn-primary" id="submit-order-btn" onclick="submitOrder()">
      Siparişi Gönder
    </button>
  </div>
</div>

<!-- PRODUCT MODAL -->
<div class="modal-overlay" id="modal-overlay" onclick="Modal.close()"></div>
<div class="modal" id="product-modal" style="position:fixed">
  <button class="modal-close" onclick="Modal.close()">✕</button>
  <div class="modal-img" id="modal-img"></div>
  <div class="modal-body">
    <h2 class="modal-title" id="modal-title"></h2>
    <p class="modal-desc" id="modal-desc"></p>
    <div class="modal-price" id="modal-price"></div>
    <div class="modal-qty-row">
      <span class="modal-qty-label">Adet</span>
      <div class="modal-qty-ctrl">
        <button class="modal-qty-btn" onclick="Modal.changeQty(-1)">−</button>
        <span class="modal-qty-num" id="modal-qty">1</span>
        <button class="modal-qty-btn" onclick="Modal.changeQty(1)">+</button>
      </div>
    </div>
    <textarea class="modal-notes" id="modal-notes" rows="2" placeholder="Özel isteğiniz var mı? (isteğe bağlı)"></textarea>
    <button class="btn btn-primary" onclick="Modal.addToCart()">Sepete Ekle</button>
  </div>
</div>

<!-- TOAST -->
<div class="toast-container" id="toast-container"></div>

<!-- HIDDEN FIELDS -->
<input type="hidden" id="table-id" value="<?= $table_id ?>">
<input type="hidden" id="session-token" value="<?= $session_token ?>">

<script src="assets/js/menu.js"></script>
</body>
</html>
