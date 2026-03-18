<?php
session_start();
require_once '../../connection/conn.php';
require_once '../../connection/logger.php';

// ບັນທຶກການເຂົ້າຊົມໜ້ານີ້
logVisitor($conn, $_SESSION['user_id'] ?? null);

// ກວດສອບສິດເຂົ້າເຖິງ (ຕ້ອງເປັນ admin)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login/index.php");
    exit;
}

// ດຶງຂໍ້ມູນ logs Login/Logout
$page = $_GET['page'] ?? 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$logs = $conn->query("
    SELECT al.*, u.name as user_name 
    FROM auth_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC 
    LIMIT $limit OFFSET $offset
");

// ນັບຈຳນວນທັງໝົດ
$total_result = $conn->query("SELECT COUNT(*) as total FROM auth_logs");
$total = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);

// ສະຖິຕິ
$stats = $conn->query("
    SELECT 
        SUM(CASE WHEN action = 'login_success' THEN 1 ELSE 0 END) as success_count,
        SUM(CASE WHEN action = 'login_failed' THEN 1 ELSE 0 END) as failed_count,
        SUM(CASE WHEN action = 'logout' THEN 1 ELSE 0 END) as logout_count
    FROM auth_logs
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ບັນທຶກ Login/Logout</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@100..900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Noto Sans Lao', sans-serif;
        }

        :root {
            --bg: #0d0f14;
            --surface: #14171f;
            --surface-2: #1c2030;
            --border: rgba(255,255,255,0.07);
            --accent: #6c63ff;
            --success: #22c55e;
            --error: #f87171;
            --warning: #fbbf24;
            --text: #e8eaf0;
            --text-muted: #7a7f96;
            --radius: 14px;
        }

        body {
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
        }

        .header {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        h1 {
            font-size: 2rem;
            font-weight: 700;
        }

        .nav-links {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 10px 20px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--surface-2);
            color: var(--text);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .btn:hover {
            background: var(--accent);
            border-color: var(--accent);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
        }

        .stat-card h3 {
            font-size: 0.85rem;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
        }

        .stat-success { color: var(--success); }
        .stat-error { color: var(--error); }
        .stat-warning { color: var(--warning); }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 24px;
        }

        .table-wrapper {
            overflow-x: auto;
            border-radius: var(--radius);
            border: 1px solid var(--border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            font-size: 0.85rem;
        }

        th {
            background: var(--surface-2);
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: 0.75rem;
        }

        tr:hover {
            background: rgba(255,255,255,0.02);
        }

        .badge {
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-success {
            background: rgba(34,197,94,0.15);
            color: var(--success);
        }

        .badge-error {
            background: rgba(248,113,113,0.15);
            color: var(--error);
        }

        .badge-warning {
            background: rgba(251,191,36,0.15);
            color: var(--warning);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
        }

        .pagination a {
            padding: 8px 16px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            color: var(--text);
            text-decoration: none;
        }

        .pagination a.active {
            background: var(--accent);
            border-color: var(--accent);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 ບັນທຶກ Login/Logout</h1>
            <div class="nav-links">
                <a href="visitor_logs.php" class="btn">ບັນທຶກການເຂົ້າຊົມ</a>
                <a href="../account/index.php" class="btn">ກັບໄປໜ້າຫຼັກ</a>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Login ສຳເລັດ</h3>
                <div class="value stat-success"><?php echo number_format($stats['success_count']); ?></div>
            </div>
            <div class="stat-card">
                <h3>Login ລົ້ມເຫລວ</h3>
                <div class="value stat-error"><?php echo number_format($stats['failed_count']); ?></div>
            </div>
            <div class="stat-card">
                <h3>Logout</h3>
                <div class="value stat-warning"><?php echo number_format($stats['logout_count']); ?></div>
            </div>
        </div>

        <div class="card">
            <h2 style="margin-bottom: 20px;">ທັງໝົດ: <?php echo number_format($total); ?> ລາຍການ</h2>
            
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ຜູ້ໃຊ້</th>
                            <th>ອີເມວ</th>
                            <th>ການກະທຳ</th>
                            <th>IP Address</th>
                            <th>ຂໍ້ຄວາມ</th>
                            <th>ເວລາ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($log = $logs->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $log['id']; ?></td>
                                <td>
                                    <?php 
                                    echo $log['user_name'] 
                                        ? htmlspecialchars($log['user_name']) 
                                        : '<span style="color: var(--text-muted);">-</span>';
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['email']); ?></td>
                                <td>
                                    <?php
                                    $action = $log['action'];
                                    if ($action === 'login_success') {
                                        echo '<span class="badge badge-success">Login ສຳເລັດ</span>';
                                    } elseif ($action === 'login_failed') {
                                        echo '<span class="badge badge-error">Login ລົ້ມເຫລວ</span>';
                                    } else {
                                        echo '<span class="badge badge-warning">Logout</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                <td><?php echo htmlspecialchars($log['status_message']); ?></td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="<?php echo $page == $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
