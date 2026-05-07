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

function normalizeSlug($slug) {
    $slug = strtolower(trim($slug ?? ''));
    if (!preg_match('/^[a-z0-9_-]{2,50}$/', $slug)) {
        sendJson(['success' => false, 'message' => 'Slug must be 2-50 characters: a-z, 0-9, underscore, or dash'], 400);
    }
    return $slug;
}

function normalizeColor($color) {
    $color = trim($color ?? '#F59E0B');
    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
        sendJson(['success' => false, 'message' => 'Color must be a valid hex color, example #F59E0B'], 400);
    }
    return strtoupper($color);
}

function formatCategory($row) {
    return [
        'id' => $row['slug'],
        'db_id' => (int)$row['id'],
        'slug' => $row['slug'],
        'name' => $row['name'],
        'icon' => $row['icon'] ?: 'fa-tag',
        'color' => $row['color'] ?: '#F59E0B',
        'product_count' => (int)($row['product_count'] ?? 0),
    ];
}

function categorySelectSql($where = '') {
    return "
        SELECT
            c.id,
            c.slug,
            c.name,
            c.icon,
            c.color,
            CASE
                WHEN c.slug = 'all' THEN (SELECT COUNT(*) FROM products)
                ELSE COUNT(p.id)
            END AS product_count
        FROM categories c
        LEFT JOIN products p ON p.category_id = c.id
        $where
        GROUP BY c.id, c.slug, c.name, c.icon, c.color
        ORDER BY c.id ASC
    ";
}

function getCategoryById($db, $id) {
    $stmt = $db->prepare(categorySelectSql("WHERE c.id = ?"));
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$row = $result->fetch_assoc()) {
        return null;
    }
    return formatCategory($row);
}

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            $category = getCategoryById($db, (int)$_GET['id']);
            if (!$category) {
                sendJson(['success' => false, 'message' => 'Category not found'], 404);
            }
            sendJson(['success' => true, 'data' => $category]);
        }

        if (isset($_GET['slug'])) {
            $slug = normalizeSlug($_GET['slug']);
            $stmt = $db->prepare(categorySelectSql("WHERE c.slug = ?"));
            $stmt->bind_param("s", $slug);
            $stmt->execute();
            $result = $stmt->get_result();
            if (!$row = $result->fetch_assoc()) {
                sendJson(['success' => false, 'message' => 'Category not found'], 404);
            }
            sendJson(['success' => true, 'data' => formatCategory($row)]);
        }

        $result = $db->query(categorySelectSql());
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = formatCategory($row);
        }
        sendJson(['success' => true, 'data' => $categories]);
        break;

    case 'POST':
        $input = readJsonBody();
        $slug = normalizeSlug($input['slug'] ?? '');
        $name = trim($input['name'] ?? '');
        $icon = trim($input['icon'] ?? 'fa-tag');
        $color = normalizeColor($input['color'] ?? '#F59E0B');

        if ($name === '') {
            sendJson(['success' => false, 'message' => 'Category name is required'], 400);
        }

        $stmt = $db->prepare("INSERT INTO categories (slug, name, icon, color) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $slug, $name, $icon, $color);

        if ($stmt->execute()) {
            sendJson([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => getCategoryById($db, $db->insert_id),
            ], 201);
        }

        if ($db->errno === 1062) {
            sendJson(['success' => false, 'message' => 'Category slug already exists'], 409);
        }
        sendJson(['success' => false, 'message' => 'Failed to create category'], 500);
        break;

    case 'PUT':
        $input = readJsonBody();
        $id = (int)($input['id'] ?? $input['db_id'] ?? 0);
        if ($id <= 0) {
            sendJson(['success' => false, 'message' => 'Category id is required'], 400);
        }

        $updates = [];
        $types = '';
        $values = [];

        if (array_key_exists('slug', $input)) {
            $updates[] = 'slug = ?';
            $types .= 's';
            $values[] = normalizeSlug($input['slug']);
        }
        if (array_key_exists('name', $input)) {
            $name = trim($input['name']);
            if ($name === '') {
                sendJson(['success' => false, 'message' => 'Category name cannot be empty'], 400);
            }
            $updates[] = 'name = ?';
            $types .= 's';
            $values[] = $name;
        }
        if (array_key_exists('icon', $input)) {
            $updates[] = 'icon = ?';
            $types .= 's';
            $values[] = trim($input['icon']) ?: 'fa-tag';
        }
        if (array_key_exists('color', $input)) {
            $updates[] = 'color = ?';
            $types .= 's';
            $values[] = normalizeColor($input['color']);
        }

        if (empty($updates)) {
            sendJson(['success' => false, 'message' => 'No fields to update'], 400);
        }

        $values[] = $id;
        $types .= 'i';
        $sql = "UPDATE categories SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$values);

        if ($stmt->execute()) {
            if ($stmt->affected_rows >= 0) {
                $category = getCategoryById($db, $id);
                if (!$category) {
                    sendJson(['success' => false, 'message' => 'Category not found'], 404);
                }
                sendJson(['success' => true, 'message' => 'Category updated successfully', 'data' => $category]);
            }
        }

        if ($db->errno === 1062) {
            sendJson(['success' => false, 'message' => 'Category slug already exists'], 409);
        }
        sendJson(['success' => false, 'message' => 'Failed to update category'], 500);
        break;

    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            sendJson(['success' => false, 'message' => 'Missing category id'], 400);
        }

        $category = getCategoryById($db, $id);
        if (!$category) {
            sendJson(['success' => false, 'message' => 'Category not found'], 404);
        }
        if ($category['slug'] === 'all') {
            sendJson(['success' => false, 'message' => 'The all category cannot be deleted'], 400);
        }
        if ($category['product_count'] > 0) {
            sendJson(['success' => false, 'message' => 'Move or delete products before deleting this category'], 409);
        }

        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            sendJson(['success' => true, 'message' => 'Category deleted successfully']);
        }
        sendJson(['success' => false, 'message' => 'Failed to delete category'], 500);
        break;

    default:
        sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
        break;
}
