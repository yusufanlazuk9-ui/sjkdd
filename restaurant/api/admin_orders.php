<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

if (empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['error' => 'Unauthorized']); exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'update_status') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $status   = $_POST['status'] ?? '';
    $allowed  = ['pending', 'preparing', 'ready', 'completed'];
    if (!$order_id || !in_array($status, $allowed)) {
        echo json_encode(['error' => 'Geçersiz istek.']); exit;
    }
    $stmt = db()->prepare('UPDATE orders SET status = ? WHERE id = ?');
    $stmt->execute([$status, $order_id]);
    echo json_encode(['success' => true]);

} elseif ($action === 'get_orders') {
    $status_filter = $_GET['status'] ?? '';
    $allowed = ['pending', 'preparing', 'ready', 'completed', ''];
    if (!in_array($status_filter, $allowed)) $status_filter = '';

    $where = $status_filter ? "WHERE o.status = ?" : "WHERE o.status != 'completed'";
    $params = $status_filter ? [$status_filter] : [];

    $stmt = db()->prepare("SELECT o.*, t.table_number FROM orders o JOIN tables t ON t.id = o.table_id $where ORDER BY o.created_at DESC LIMIT 100");
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    foreach ($orders as &$order) {
        $ist = db()->prepare('SELECT oi.quantity, p.name FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?');
        $ist->execute([$order['id']]);
        $order['items'] = $ist->fetchAll();
    }
    echo json_encode(['orders' => $orders]);
} else {
    echo json_encode(['error' => 'Unknown action']);
}
