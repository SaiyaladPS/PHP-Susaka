<?php
session_start();

include '../connection/conn.php';
include '../connection/logger.php';

// ບັນທຶກການ Logout
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['user_name'] ?? '';
    
    // ດຶງອີເມວຂອງຜູ້ໃຊ້
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if ($user) {
        logAuth($conn, 'logout', $user['email'], $user_id, 'ອອກຈາກລະບົບສຳເລັດ');
    }
}

// ລຶບ session
session_unset();
session_destroy();

$conn->close();

// ກັບໄປໜ້າ login
header("Location: login/index.php");
exit;
?>
