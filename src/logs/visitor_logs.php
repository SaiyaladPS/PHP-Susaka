<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/logger.php';

logVisitor($conn, $_SESSION['user_id'] ?? null);

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: /src/login/index.php");
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

$logs = $conn->query("
    SELECT vl.*, u.name as user_name, u.email 
    FROM visitor_logs vl 
    LEFT JOIN users u ON vl.user_id = u.id 
    ORDER BY vl.created_at DESC 
    LIMIT $limit OFFSET $offset
");

$total = (int)$conn->query("SELECT COUNT(*) AS c FROM visitor_logs")->fetch_assoc()['c'];
$total_pages = (int)ceil($total / $limit);

$page_title = 'ບັນທຶກຜູ້ເຂົ້າຊົມ';
$active_nav = 'visitor-logs';
require_once __DIR__ . '/../../includes/layout/header.php';
?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            ທັງໝົດ <?= number_format($total) ?> ລາຍການ
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>ຜູ້ໃຊ້</th><th>IP</th>
                    <th>ໜ້າ URL</th><th>User Agent</th><th>ເວລາ</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($log = $logs->fetch_assoc()): ?>
                <tr>
                    <td><?= $log['id'] ?></td>
                    <td>
                        <?php if ($log['user_name']): ?>
                            <?= htmlspecialchars($log['user_name']) ?>
                            <br><small style="color:var(--text-muted);"><?= htmlspecialchars($log['email']) ?></small>
                        <?php else: ?>
                            <span class="badge badge-muted">Guest</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                    <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <?= htmlspecialchars($log['page_url']) ?>
                    </td>
                    <td style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--text-muted);">
                        <?= htmlspecialchars($log['user_agent']) ?>
                    </td>
                    <td style="color:var(--text-muted);white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>" class="<?= $page == $i ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
require_once __DIR__ . '/../../includes/layout/footer.php';
?>
