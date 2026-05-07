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

function normalizeMoney($value) {
    return round((float)$value, 2);
}

function normalizeSlug($slug) {
    $slug = strtolower(trim($slug ?? ''));
    if (!preg_match('/^[a-z0-9_-]{2,50}$/', $slug)) {
        sendJson(['success' => false, 'message' => 'Invalid category slug'], 400);
    }
    return $slug;
}

function formatProduct($row) {
    return [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'price' => normalizeMoney($row['price']),
        'category_id' => $row['category_id'] === null ? null : (int)$row['category_id'],
        'category_name' => $row['category_name'],
        'cat' => $row['category_slug'],
        'category_slug' => $row['category_slug'],
        'img' => $row['image_url'],
        'image_url' => $row['image_url'],
    ];
}

function productSelectSql($where = '') {
    return "
        SELECT
            p.id,
            p.name,
            p.price,
            p.category_id,
            p.image_url,
            c.slug AS category_slug,
            c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        $where
    ";
}

function getProductById($db, $id) {
    $stmt = $db->prepare(productSelectSql("WHERE p.id = ?"));
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$row = $result->fetch_assoc()) {
        return null;
    }
    return formatProduct($row);
}

function getCategoryId($db, $input) {
    $categoryId = (int)($input['category_id'] ?? 0);
    if ($categoryId > 0) {
        $stmt = $db->prepare("SELECT id FROM categories WHERE id = ?");
        $stmt->bind_param("i", $categoryId);
    } else {
        $slug = normalizeSlug($input['category_slug'] ?? $input['cat'] ?? '');
        if ($slug === 'all') {
            sendJson(['success' => false, 'message' => 'Product category cannot be all'], 400);
        }
        $stmt = $db->prepare("SELECT id FROM categories WHERE slug = ?");
        $stmt->bind_param("s", $slug);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    if (!$row = $result->fetch_assoc()) {
        sendJson(['success' => false, 'message' => 'Category not found'], 404);
    }
    return (int)$row['id'];
}

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $product = getProductById($db, (int)$_GET['id']);
            if (!$product) {
                sendJson(['success' => false, 'message' => 'Product not found'], 404);
            }
            sendJson(['success' => true, 'data' => $product]);
        }

        $where = [];
        $types = '';
        $values = [];

        if (isset($_GET['category_id'])) {
            $where[] = 'p.category_id = ?';
            $types .= 'i';
            $values[] = (int)$_GET['category_id'];
        }

        if (isset($_GET['category']) || isset($_GET['cat'])) {
            $slug = normalizeSlug($_GET['category'] ?? $_GET['cat']);
            if ($slug !== 'all') {
                $where[] = 'c.slug = ?';
                $types .= 's';
                $values[] = $slug;
            }
        }

        if (isset($_GET['search']) && trim($_GET['search']) !== '') {
            $where[] = 'p.name LIKE ?';
            $types .= 's';
            $values[] = '%' . trim($_GET['search']) . '%';
        }

        $whereSql = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        $limit = min(max((int)($_GET['limit'] ?? 100), 1), 200);
        $sql = productSelectSql($whereSql) . " ORDER BY p.id ASC LIMIT ?";
        $types .= 'i';
        $values[] = $limit;

        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];

        while ($row = $result->fetch_assoc()) {
            $products[] = formatProduct($row);
        }

        sendJson(['success' => true, 'data' => $products]);
        break;

    case 'POST':
        $input = readJsonBody();
        $name = trim($input['name'] ?? '');
        $price = normalizeMoney($input['price'] ?? 0);
        $categoryId = getCategoryId($db, $input);
        $imageUrl = trim($input['image_url'] ?? $input['img'] ?? '');

        if ($name === '') {
            sendJson(['success' => false, 'message' => 'Product name is required'], 400);
        }
        if ($price <= 0) {
            sendJson(['success' => false, 'message' => 'Product price must be greater than 0'], 400);
        }

        $stmt = $db->prepare("INSERT INTO products (name, price, category_id, image_url) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdis", $name, $price, $categoryId, $imageUrl);

        if ($stmt->execute()) {
            sendJson([
                'success' => true,
                'message' => 'Product created successfully',
                'data' => getProductById($db, $db->insert_id),
            ], 201);
        }

        sendJson(['success' => false, 'message' => 'Failed to create product'], 500);
        break;

    case 'PUT':
        $input = readJsonBody();
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            sendJson(['success' => false, 'message' => 'Product id is required'], 400);
        }

        $updates = [];
        $types = '';
        $values = [];

        if (array_key_exists('name', $input)) {
            $name = trim($input['name']);
            if ($name === '') {
                sendJson(['success' => false, 'message' => 'Product name cannot be empty'], 400);
            }
            $updates[] = 'name = ?';
            $types .= 's';
            $values[] = $name;
        }

        if (array_key_exists('price', $input)) {
            $price = normalizeMoney($input['price']);
            if ($price <= 0) {
                sendJson(['success' => false, 'message' => 'Product price must be greater than 0'], 400);
            }
            $updates[] = 'price = ?';
            $types .= 'd';
            $values[] = $price;
        }

        if (array_key_exists('category_id', $input) || array_key_exists('category_slug', $input) || array_key_exists('cat', $input)) {
            $updates[] = 'category_id = ?';
            $types .= 'i';
            $values[] = getCategoryId($db, $input);
        }

        if (array_key_exists('image_url', $input) || array_key_exists('img', $input)) {
            $updates[] = 'image_url = ?';
            $types .= 's';
            $values[] = trim($input['image_url'] ?? $input['img'] ?? '');
        }

        if (empty($updates)) {
            sendJson(['success' => false, 'message' => 'No fields to update'], 400);
        }

        $values[] = $id;
        $types .= 'i';
        $sql = "UPDATE products SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$values);

        if ($stmt->execute()) {
            $product = getProductById($db, $id);
            if (!$product) {
                sendJson(['success' => false, 'message' => 'Product not found'], 404);
            }
            sendJson(['success' => true, 'message' => 'Product updated successfully', 'data' => $product]);
        }

        sendJson(['success' => false, 'message' => 'Failed to update product'], 500);
        break;

    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            sendJson(['success' => false, 'message' => 'Missing product id'], 400);
        }

        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                sendJson(['success' => true, 'message' => 'Product deleted successfully']);
            }
            sendJson(['success' => false, 'message' => 'Product not found'], 404);
        }

        sendJson(['success' => false, 'message' => 'Failed to delete product'], 500);
        break;

    default:
        sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
        break;
}
