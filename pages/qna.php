<?php
require_once __DIR__ . '/../config/db.php';
$conn = getDBConnection();

$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Tổng số QnA - Prepared statement
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM qna");
$stmt->execute();
$totalResult = $stmt->get_result();
$total = $totalResult->fetch_assoc()['total'];
$totalPages = max(1, ceil($total / $perPage));

// Lấy danh sách QnA - Prepared statement + chỉ SELECT columns cần thiết
$qnasQuery = "
    SELECT q.qna_id, q.category_id, q.question, q.answer, q.created_at,
           qc.category_name
    FROM qna q
    LEFT JOIN qna_category qc ON q.category_id = qc.category_id
    ORDER BY q.created_at DESC
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($qnasQuery);
$stmt->bind_param('ii', $perPage, $offset);
$stmt->execute();
$qnas = $stmt->get_result();

closeDBConnection($conn);
?>

<div id="qna" class="page <?php echo ($currentPage == 'qna') ? 'active' : ''; ?>">
    <div class="card">
        <div class="card-header">
            <h2>Quản lý QnA</h2>
            <button class="btn btn-primary" onclick="openModal('addQnaModal')">
                <i class="fas fa-plus"></i> Thêm QnA
            </button>
        </div>
        <div class="card-body">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category</th>
                        <th>Question</th>
                        <th>Answer</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($qnas && $qnas->num_rows > 0): ?>
                        <?php while($q = $qnas->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $q['qna_id']; ?></td>
                                <td><?php echo htmlspecialchars($q['category_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars(substr($q['question'], 0, 50)) . '...'; ?></td>
                                <td><?php echo htmlspecialchars(substr($q['answer'], 0, 50)) . '...'; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($q['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <div class="action-btn btn-info" 
                                             onclick="editQna(<?php echo $q['qna_id']; ?>)"
                                             title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </div>
                                        <div class="action-btn btn-danger"
                                             onclick="deleteQna(<?php echo $q['qna_id']; ?>)"
                                             title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <i class="fas fa-question-circle"></i>
                                <p>Chưa có QnA nào</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <div class="page-item">
                            <a href="?page=qna&p=<?php echo $page - 1; ?>" class="page-link">Previous</a>
                        </div>
                    <?php endif; ?>
                    <?php for($i = 1; $i <= $totalPages; $i++): ?>
                        <div class="page-item">
                            <a href="?page=qna&p=<?php echo $i; ?>" 
                               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </div>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <div class="page-item">
                            <a href="?page=qna&p=<?php echo $page + 1; ?>" class="page-link">Next</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function editQna(id) { alert('Edit QnA: ' + id); }
function deleteQna(id) {
    if (confirm('Xóa QnA này?')) {
        fetch('api/delete_qna.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({qna_id: id})
        }).then(r => r.json()).then(d => {
            if (d.success) location.reload();
            else alert('Error: ' + d.message);
        });
    }
}
</script>

