<?php
require_once __DIR__ . '/../config/db.php';
$conn = getDBConnection();

$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$totalResult = $conn->query("SELECT COUNT(*) as total FROM system_logs");
$total = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($total / $perPage);

$logs = $conn->query("
    SELECT sl.*, u.username
    FROM system_logs sl
    LEFT JOIN users u ON sl.user_id = u.user_id
    ORDER BY sl.created_at DESC
    LIMIT $perPage OFFSET $offset
");

closeDBConnection($conn);
?>

<div id="system-logs" class="page <?php echo ($currentPage == 'system-logs') ? 'active' : ''; ?>">
    <div class="card">
        <div class="card-header">
            <h2>System Logs</h2>
        </div>
        <div class="card-body">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>Ngày tạo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logs && $logs->num_rows > 0): ?>
                        <?php while($log = $logs->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $log['log_id']; ?></td>
                                <td><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                                <td><?php echo htmlspecialchars(substr($log['details'] ?? '', 0, 100)); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty-state">
                                <i class="fas fa-clipboard-list"></i>
                                <p>Chưa có log nào</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <div class="page-item">
                            <a href="?page=system-logs&p=<?php echo $page - 1; ?>" class="page-link">Previous</a>
                        </div>
                    <?php endif; ?>
                    <?php for($i = 1; $i <= $totalPages; $i++): ?>
                        <div class="page-item">
                            <a href="?page=system-logs&p=<?php echo $i; ?>" 
                               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </div>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <div class="page-item">
                            <a href="?page=system-logs&p=<?php echo $page + 1; ?>" class="page-link">Next</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

