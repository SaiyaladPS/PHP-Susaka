<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getInstance()->getConnection();

switch ($method) {
    case 'GET':
        // GET single user or all users
        if (isset($_GET['id'])) {
            $stmt = $db->prepare("SELECT id, name, email, role, last_seen, created_at, updated_at FROM users WHERE id = ?");
            $stmt->bind_param("i", $_GET['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                echo json_encode(['success' => true, 'data' => $row]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
        } else {
            $sql = "SELECT id, name, email, role, last_seen, created_at, updated_at FROM users";
            $result = $db->query($sql);
            $users = [];
            
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            
            echo json_encode(['success' => true, 'data' => $users]);
        }
        break;
    
    case 'POST':
        // Create new user
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['name']) || !isset($input['email']) || !isset($input['password'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields: name, email, password']);
            break;
        }
        
        $name = $input['name'];
        $email = $input['email'];
        $password = password_hash($input['password'], PASSWORD_DEFAULT);
        $role = $input['role'] ?? 'user';
        
        // Check if email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            break;
        }
        
        $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $password, $role);
        
        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode([
                'success' => true, 
                'message' => 'User created successfully',
                'data' => ['id' => $db->insert_id]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to create user']);
        }
        break;
    
    case 'PUT':
        // Update user
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing user id']);
            break;
        }
        
        $id = $input['id'];
        $updates = [];
        $types = "";
        $values = [];
        
        if (isset($input['name'])) {
            $updates[] = "name = ?";
            $types .= "s";
            $values[] = $input['name'];
        }
        if (isset($input['email'])) {
            $updates[] = "email = ?";
            $types .= "s";
            $values[] = $input['email'];
        }
        if (isset($input['password'])) {
            $updates[] = "password = ?";
            $types .= "s";
            $values[] = password_hash($input['password'], PASSWORD_DEFAULT);
        }
        if (isset($input['role'])) {
            $updates[] = "role = ?";
            $types .= "s";
            $values[] = $input['role'];
        }
        
        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            break;
        }
        
        $updates[] = "updated_at = CURRENT_TIMESTAMP";
        $values[] = $id;
        $types .= "i";
        
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$values);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User not found or no changes made']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update user']);
        }
        break;
    
    case 'DELETE':
        // Delete user
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'User not found']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing user id']);
        }
        break;
    
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        break;
}
