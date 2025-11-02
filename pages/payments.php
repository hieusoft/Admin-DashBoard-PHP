<?php
require_once __DIR__ . '/../config/db.php';

$conn = getDBConnection();

$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Tổng số payment
$totalResult = $conn->query("SELECT COUNT(*) as total FROM payments");
$totalPayments = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalPayments / $perPage);

// Lấy danh sách payments
$payments = $conn->query("
    SELECT p.*, sp.name as plan_name
    FROM payments p
    LEFT JOIN subscription_plans sp ON p.plan_id = sp.plan_id
    ORDER BY p.created_at DESC
    LIMIT $perPage OFFSET $offset
");

closeDBConnection($conn);
?>

<div id="payments" class="page <?php echo ($currentPage == 'payments') ? 'active' : ''; ?>">
    <div class="card">
        <div class="card-header">
            <h2>Quản lý Payments</h2>
            <!-- ĐÃ XÓA NÚT "Thêm Payment" -->
        </div>
        <div class="card-body">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Order ID</th>
                        <th>User ID</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Currency</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($payments && $payments->num_rows > 0): ?>
                        <?php while($payment = $payments->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $payment['payment_id']; ?></td>
                                <td>#<?php echo $payment['order_id']; ?></td>
                                <td><?php echo $payment['user_id'] ?? 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars($payment['plan_name'] ?? 'N/A'); ?></td>
                                <td><?php echo number_format($payment['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($payment['currency']); ?></td>
                                <td><?php echo htmlspecialchars($payment['method']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $payment['status']; ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <div class="action-btn" style="background-color: var(--info-color);" 
                                             onclick="editPayment(<?php echo $payment['payment_id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </div>
                                        <div class="action-btn" style="background-color: var(--danger-color);"
                                             onclick="deletePayment(<?php echo $payment['payment_id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="empty-state">
                                Chưa có payment nào
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Phân trang -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <div class="page-item">
                            <a href="?page=payments&p=<?php echo $page - 1; ?>" class="page-link">Previous</a>
                        </div>
                    <?php endif; ?>
                    <?php for($i = 1; $i <= $totalPages; $i++): ?>
                        <div class="page-item">
                            <a href="?page=payments&p=<?php echo $i; ?>" 
                               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </div>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <div class="page-item">
                            <a href="?page=payments&p=<?php echo $page + 1; ?>" class="page-link">Next</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Payment Modal -->
<div id="editPaymentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Sửa Payment</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="editPaymentForm" onsubmit="return false;">
                <input type="hidden" name="payment_id" id="edit_payment_id">
                <input type="hidden" name="user_id" id="edit_user_id_hidden">

                <!-- Order ID: chỉ hiển thị, không cho sửa -->
                <div class="form-group">
                    <label>Order ID</label>
                    <input type="text" id="edit_order_id_display" class="form-control" disabled>
                </div>

                <!-- User ID: chỉ hiển thị -->
                <div class="form-group">
                    <label>User ID</label>
                    <input type="number" id="edit_user_id_display" class="form-control" disabled>
                </div>

                <!-- Các trường được phép sửa -->
                <div class="form-group">
                    <label>Plan ID</label>
                    <input type="number" name="plan_id" id="edit_plan_id" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Amount</label>
                    <input type="number" name="amount" id="edit_amount" class="form-control" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Currency</label>
                    <input type="text" name="currency" id="edit_currency" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Method</label>
                    <input type="text" name="method" id="edit_method" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status" class="form-control">
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-danger" onclick="closeModal()">Hủy</button>
            <button class="btn btn-primary" onclick="submitEditPayment()">Cập nhật</button>
        </div>
    </div>
</div>

<script>
// Mở / đóng modal
function openModal(id) {
    document.getElementById(id).style.display = 'block';
}
function closeModal() {
    document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
}

// === Sửa Payment ===
function editPayment(id) {
    fetch('api/get_payment.php?id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.payment) {
                const p = data.payment;

                // Gán giá trị vào form
                document.getElementById('edit_payment_id').value = p.payment_id;
                document.getElementById('edit_user_id_hidden').value = p.user_id;
                document.getElementById('edit_user_id_display').value = p.user_id;
                document.getElementById('edit_order_id_display').value = p.order_id;

                document.getElementById('edit_plan_id').value = p.plan_id;
                document.getElementById('edit_amount').value = p.amount;
                document.getElementById('edit_currency').value = p.currency;
                document.getElementById('edit_method').value = p.method;
                document.getElementById('edit_status').value = p.status;

                openModal('editPaymentModal');
            } else {
                alert('Không thể tải payment: ' + (data.message || 'Lỗi'));
            }
        })
        .catch(err => {
            console.error(err);
            alert('Lỗi khi kết nối server.');
        });
}

// === Gửi form cập nhật ===
function submitEditPayment() {
    const form = document.getElementById('editPaymentForm');
    const data = Object.fromEntries(new FormData(form));

    // Không gửi order_id và user_id vì không cho sửa
    delete data.order_id;
    delete data.user_id;

    fetch('api/update_payment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            closeModal();
            location.reload();
        } else {
            alert(result.message || 'Cập nhật thất bại');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Lỗi khi kết nối server.');
    });
}

// === Xóa Payment ===
function deletePayment(id) {
    if (!confirm('Xóa payment này?')) return;

    fetch('api/delete_payment.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({payment_id: id})
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) location.reload();
        else alert(data.message || 'Lỗi khi xóa');
    })
    .catch(err => {
        console.error(err);
        alert('Lỗi khi kết nối server.');
    });
}
</script>
