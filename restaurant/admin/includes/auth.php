<?php
require_once __DIR__ . '/../../includes/config.php';
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: /restaurant/admin/login.php');
    exit;
}

function adminNav($active = '') {
    $nav = [
        ['href' => 'index.php',      'icon' => '📊', 'label' => 'Dashboard'],
        ['href' => 'orders.php',     'icon' => '📋', 'label' => 'Siparişler'],
        ['href' => 'tables.php',     'icon' => '🪑', 'label' => 'Masalar'],
        ['href' => 'categories.php', 'icon' => '🗂️', 'label' => 'Kategoriler'],
        ['href' => 'products.php',   'icon' => '🍽️', 'label' => 'Ürünler'],
        ['href' => 'logout.php',     'icon' => '🚪', 'label' => 'Çıkış'],
    ];
    echo '<nav class="admin-nav">';
    echo '<div class="nav-section">YÖNETİM</div>';
    foreach ($nav as $item) {
        $cls = ($active === $item['label']) ? ' active' : '';
        echo "<a href='{$item['href']}' class='$cls'><span class='nav-icon'>{$item['icon']}</span>{$item['label']}</a>";
    }
    echo '</nav>';
}
