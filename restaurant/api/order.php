<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['error' => 'Invalid JSON']); exit;
}

$table_id     = (int)($input['table_id'] ?? 0);
$session_token = preg_replace('/[^a-f0-9]/', '', $input['session_token'] ?? '');
$items        = $input['items'] ?? [];
$notes        = mb_substr(trim($input['notes'] ?? ''), 0, 500);

if (!$table_id || empty($session_token) || empty($items)) {
    echo json_encode(['error' => 'Eksik bilgi.']); exit;
}

// Validate session matches
if (empty($_SESSION['table_id']) || (int)$_SESSION['table_id'] !== $table_id || $_SESSION['session_token'] !== $session_token) {
    echo json_encode(['error' => 'Oturum geçersiz.']); exit;
}

// Verify table exists
$stmt = db()->prepare('SELECT id FROM tables WHERE id = ? AND is_active = 1');
$stmt->execute([$table_id]);
if (!$stmt->fetch()) {
    echo json_encode(['error' => 'Masa bulunamadı.']); exit;
}

// Validate + price products from DB
$product_ids = array_map(fn($i) => (int)($i['id'] ?? 0), $items);
$product_ids = array_filter($product_ids);
if (empty($product_ids)) {
    echo json_encode(['error' => 'Geçersiz ürünler.']); exit;
}

$in = implode(',', array_fill(0, count($product_ids), '?'));
$stmt = db()->prepare("SELECT id, price FROM products WHERE id IN ($in) AND is_active = 1");
$stmt->execute($product_ids);
$db_products = [];
foreach ($stmt->fetchAll() as $p) {
    $db_products[$p['id']] = $p['price'];
}

$total = 0;
$validated_items = [];
foreach ($items as $item) {
    $pid = (int)($item['id'] ?? 0);
    $qty = max(1, (int)($item['qty'] ?? 1));
    $notes_item = mb_substr(trim($item['notes'] ?? ''), 0, 200);
    if (!isset($db_products[$pid])) continue;
    $price = (float)$db_products[$pid];
    $total += $price * $qty;
    $validated_items[] = ['product_id' => $pid, 'quantity' => $qty, 'unit_price' => $price, 'notes' => $notes_item];
}

if (empty($validated_items)) {
    echo json_encode(['error' => 'Geçerli ürün yok.']); exit;
}

try {
    $pdo = db();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('INSERT INTO orders (table_id, session_token, total_amount, notes, status) VALUES (?, ?, ?, ?, \'pending\')');
    $stmt->execute([$table_id, $session_token, $total, $notes]);
    $order_id = $pdo->lastInsertId();

    $stmt2 = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price, notes) VALUES (?, ?, ?, ?, ?)');
    foreach ($validated_items as $v) {
        $stmt2->execute([$order_id, $v['product_id'], $v['quantity'], $v['unit_price'], $v['notes']]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'order_id' => $order_id]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => 'Sipariş oluşturulamadı.']);
}
