<?php
// ຟັງຊັນສຳລັບບັນທຶກການເຂົ້າຊົມທົ່ວໄປ
function logVisitor($conn, $user_id = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $page_url = $_SERVER['REQUEST_URI'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $session_id = session_id();
    
    // ບັນທຶກລົງຖານຂໍ້ມູນ
    $stmt = $conn->prepare("INSERT INTO visitor_logs (ip_address, user_agent, page_url, referer, session_id, user_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $ip_address, $user_agent, $page_url, $referer, $session_id, $user_id);
    $stmt->execute();
    $stmt->close();
    
    // ບັນທຶກລົງໄຟລ์ .log
    $log_file = __DIR__ . '/../logs/visitor.log';
    $timestamp = date('Y-m-d H:i:s');
    $user_info = $user_id ? "User ID: $user_id" : "Guest";
    $log_message = "[$timestamp] $user_info | IP: $ip_address | Page: $page_url | Session: $session_id\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// ຟັງຊັນສຳລັບບັນທຶກການ Login/Logout
function logAuth($conn, $action, $email, $user_id = null, $status_message = '') {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // ບັນທຶກລົງຖານຂໍ້ມູນ
    $stmt = $conn->prepare("INSERT INTO auth_logs (user_id, email, action, ip_address, user_agent, status_message) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $email, $action, $ip_address, $user_agent, $status_message);
    $stmt->execute();
    $stmt->close();
    
    // ບັນທຶກລົງໄຟລ์ .log
    $log_file = __DIR__ . '/../logs/auth.log';
    $timestamp = date('Y-m-d H:i:s');
    $user_info = $user_id ? "User ID: $user_id" : "Unknown";
    $log_message = "[$timestamp] $action | $user_info | Email: $email | IP: $ip_address | Status: $status_message\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
}
?>
