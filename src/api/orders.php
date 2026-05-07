<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getInstance()->getConnection();

function sendJson($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function readJsonBody() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        sendJson(['success' => false, 'message' => 'Invalid JSON body'], 400);
    }
    return $input;
}

function toMoney($value) {
    return round((float)$value, 2);
}

function toIntOrNull($value) {
    if (is_int($value)) {
        return $value > 0 ? $value : null;
    }
    if (is_string($value) && ctype_digit($value)) {
        $number = (int)$value;
        return $number > 0 ? $number : null;
    }
    return null;
}

function formatOrder($order, $items) {
    $created = new DateTime($order['created_at']);
    $payment = null;

    if (!empty($order['payment_method'])) {
        $payment = ['method' => $order['payment_method']];
        if ($order['payment_method'] === 'cash') {
            $payment['amount'] = toMoney($order['cash_received']);
            $payment['change'] = toMoney($order['change_amount']);
        }
    }

    return [
        'id' => (int)$order['id'],
        'status' => $order['status'],
        'time' => $created->format('h:i A'),
        'date' => $created->format('M j, Y'),
        'created_at' => $order['created_at'],
        'cashier_name' => $order['cashier_name'],
        'items' => array_map(function ($item) {
            $productId = $item['product_id'] === null ? 'custom_' . $item['id'] : (int)$item['product_id'];
            return [
                'id' => (int)$item['id'],
                'product' => [
                    'id' => $productId,
                    'name' => $item['product_name'],
                    'price' => toMoney($item['unit_price']),
                    'img' => $item['image_url'],
                ],
                'quantity' => (int)$item['quantity'],
                'note' => $item['note'] ?? '',
                'lineTotal' => toMoney($item['line_total']),
            ];
        }, $items),
        'totals' => [
            'subtotal' => toMoney($order['subtotal']),
            'discAmt' => toMoney($order['discount_amount']),
            'tax' => toMoney($order['tax_amount']),
            'total' => toMoney($order['total']),
        ],
        'discount' => [
            'type' => $order['discount_type'] ?: 'percent',
            'value' => toMoney($order['discount_value']),
        ],
        'payment' => $payment,
    ];
}

function getOrderItems($db, $orderId) {
    $stmt = $db->prepare("
        SELECT oi.*, p.image_url
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];

    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    return $items;
}

function getOrderById($db, $orderId) {
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$order = $result->fetch_assoc()) {
        return null;
    }

    return formatOrder($order, getOrderItems($db, (int)$order['id']));
}

function normalizeCartItems($items) {
    if (!is_array($items) || count($items) === 0) {
        sendJson(['success' => false, 'message' => 'Order must contain at least one item'], 400);
    }

    return array_map(function ($item) {
        $product = $item['product'] ?? [];
        $name = trim($item['product_name'] ?? $product['name'] ?? '');
        $unitPrice = toMoney($item['unit_price'] ?? $product['price'] ?? 0);
        $quantity = (int)($item['quantity'] ?? 0);

        if ($name === '' || $unitPrice <= 0 || $quantity <= 0) {
            sendJson(['success' => false, 'message' => 'Each item needs name, unit price, and quantity'], 400);
        }

        return [
            'product_id' => toIntOrNull($item['product_id'] ?? $product['id'] ?? null),
            'product_name' => $name,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'line_total' => toMoney($unitPrice * $quantity),
            'note' => trim($item['note'] ?? ''),
        ];
    }, $items);
}

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $order = getOrderById($db, (int)$_GET['id']);
            if (!$order) {
                sendJson(['success' => false, 'message' => 'Order not found'], 404);
            }
            sendJson(['success' => true, 'data' => $order]);
        }

        $status = $_GET['status'] ?? 'completed';
        if (!in_array($status, ['completed', 'held'], true)) {
            sendJson(['success' => false, 'message' => 'Invalid order status'], 400);
        }

        $limit = min(max((int)($_GET['limit'] ?? 50), 1), 100);
        $stmt = $db->prepare("SELECT * FROM orders WHERE status = ? ORDER BY created_at DESC, id DESC LIMIT ?");
        $stmt->bind_param("si", $status, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = [];

        while ($row = $result->fetch_assoc()) {
            $orders[] = formatOrder($row, getOrderItems($db, (int)$row['id']));
        }

        sendJson(['success' => true, 'data' => $orders]);
        break;

    case 'POST':
        $input = readJsonBody();
        $status = $input['status'] ?? 'completed';
        if (!in_array($status, ['completed', 'held'], true)) {
            sendJson(['success' => false, 'message' => 'Invalid order status'], 400);
        }

        $items = normalizeCartItems($input['items'] ?? []);
        $subtotal = toMoney(array_reduce($items, function ($sum, $item) {
            return $sum + $item['line_total'];
        }, 0));

        $discount = $input['discount'] ?? [];
        $discountType = $discount['type'] ?? 'percent';
        $discountValue = toMoney($discount['value'] ?? 0);
        if (!in_array($discountType, ['percent', 'fixed'], true)) {
            $discountType = 'percent';
        }
        if ($discountType === 'percent') {
            $discountValue = min(max($discountValue, 0), 100);
            $discountAmount = toMoney($subtotal * ($discountValue / 100));
        } else {
            $discountValue = max($discountValue, 0);
            $discountAmount = toMoney(min($discountValue, $subtotal));
        }

        $taxRate = round((float)($input['tax_rate'] ?? 0.08), 4);
        $taxAmount = toMoney(($subtotal - $discountAmount) * $taxRate);
        $total = toMoney($subtotal - $discountAmount + $taxAmount);
        $totalItems = array_reduce($items, function ($sum, $item) {
            return $sum + $item['quantity'];
        }, 0);

        $payment = $input['payment'] ?? null;
        $paymentMethod = null;
        $cashReceived = null;
        $changeAmount = null;

        if ($status === 'completed') {
            $paymentMethod = $payment['method'] ?? null;
            if (!in_array($paymentMethod, ['cash', 'card', 'digital'], true)) {
                sendJson(['success' => false, 'message' => 'Payment method is required'], 400);
            }
            if ($paymentMethod === 'cash') {
                $cashReceived = toMoney($payment['amount'] ?? 0);
                if ($cashReceived < $total) {
                    sendJson(['success' => false, 'message' => 'Cash received is less than order total'], 400);
                }
                $changeAmount = toMoney($cashReceived - $total);
            }
        }

        $cashierId = toIntOrNull($input['cashier_id'] ?? null);
        $cashierName = trim($input['cashier_name'] ?? 'JD') ?: 'JD';

        $db->begin_transaction();
        try {
            $stmt = $db->prepare("
                INSERT INTO orders (
                    cashier_id, cashier_name, status, subtotal, tax_rate, tax_amount,
                    discount_type, discount_value, discount_amount, total, total_items,
                    payment_method, cash_received, change_amount
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "issdddsdddisdd",
                $cashierId,
                $cashierName,
                $status,
                $subtotal,
                $taxRate,
                $taxAmount,
                $discountType,
                $discountValue,
                $discountAmount,
                $total,
                $totalItems,
                $paymentMethod,
                $cashReceived,
                $changeAmount
            );
            $stmt->execute();
            $orderId = $db->insert_id;

            $itemStmt = $db->prepare("
                INSERT INTO order_items (
                    order_id, product_id, product_name, unit_price, quantity, line_total, note
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($items as $item) {
                $itemStmt->bind_param(
                    "iisdids",
                    $orderId,
                    $item['product_id'],
                    $item['product_name'],
                    $item['unit_price'],
                    $item['quantity'],
                    $item['line_total'],
                    $item['note']
                );
                $itemStmt->execute();
            }

            $db->commit();
            sendJson([
                'success' => true,
                'message' => 'Order saved successfully',
                'data' => getOrderById($db, $orderId),
            ], 201);
        } catch (Throwable $e) {
            $db->rollback();
            sendJson(['success' => false, 'message' => 'Failed to save order'], 500);
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            sendJson(['success' => false, 'message' => 'Missing order id'], 400);
        }

        $id = (int)$_GET['id'];
        $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendJson(['success' => true, 'message' => 'Order deleted successfully']);
            }
            sendJson(['success' => false, 'message' => 'Order not found'], 404);
        }

        sendJson(['success' => false, 'message' => 'Failed to delete order'], 500);
        break;

    default:
        sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
        break;
}
