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

// ດຶງຂໍ້ມູນ logs ການເຂົ້າຊົມ
$page = $_GET['page'] ?? 1;
$limit = 50;
$offset = ($page - 1) * $limit;

$logs = $conn->query("
    SELECT vl.*, u.name as user_name, u.email 
    FROM visitor_logs vl 
    LEFT JOIN users u ON vl.user_id = u.id 
    ORDER BY vl.created_at DESC 
    LIMIT $limit OFFSET $offset
");

// ນັບຈຳນວນທັງໝົດ
$total_result = $conn->query("SELECT COUNT(*) as total FROM visitor_logs");
$total = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ບັນທຶກການເຂົ້າຊົມ</title>
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
            <h1>📊 ບັນທຶກການເຂົ້າຊົມ</h1>
            <div class="nav-links">
                <a href="auth_logs.php" class="btn">ບັນທຶກ Login/Logout</a>
                <a href="../account/index.php" class="btn">ກັບໄປໜ້າຫຼັກ</a>
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
                            <th>IP Address</th>
                            <th>ໜ້າທີ່ເຂົ້າເບິ່ງ</th>
                            <th>User Agent</th>
                            <th>ເວລາ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($log = $logs->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $log['id']; ?></td>
                                <td>
                                    <?php 
                                    if ($log['user_name']) {
                                        echo htmlspecialchars($log['user_name']) . '<br><small style="color: var(--text-muted);">' . htmlspecialchars($log['email']) . '</small>';
                                    } else {
                                        echo '<span style="color: var(--text-muted);">Guest</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                <td><?php echo htmlspecialchars($log['page_url']); ?></td>
                                <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo htmlspecialchars($log['user_agent']); ?>
                                </td>
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
