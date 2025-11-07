<?php
require_once __DIR__ . '/../config/db.php';
$conn = getDBConnection();

$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Tổng số withdrawals - Prepared statement
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM affiliate_withdrawals");
$stmt->execute();
$totalResult = $stmt->get_result();
$total = $totalResult->fetch_assoc()['total'];
$totalPages = max(1, ceil($total / $perPage));

// Lấy danh sách withdrawals - Prepared statement + chỉ SELECT columns cần thiết
$withdrawalsQuery = "
    SELECT aw.withdraw_id, aw.user_id, aw.amount, aw.wallet_address, 
           aw.status, aw.tx_hash, aw.created_at, u.username
    FROM affiliate_withdrawals aw
    LEFT JOIN users u ON aw.user_id = u.user_id
    ORDER BY aw.created_at DESC
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($withdrawalsQuery);
$stmt->bind_param('ii', $perPage, $offset);
$stmt->execute();
$withdrawals = $stmt->get_result();

closeDBConnection($conn);
?>

<div id="affiliate-withdrawals" class="page <?php echo ($currentPage == 'affiliate-withdrawals') ? 'active' : ''; ?>">
    <div class="card">
        <div class="card-header">
            <h2>Affiliate Withdrawals</h2>
        </div>
        <div class="card-body">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Wallet</th>
                        <th>Status</th>
                        <th>TX Hash</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($withdrawals && $withdrawals->num_rows > 0): ?>
                        <?php while($wd = $withdrawals->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $wd['withdraw_id']; ?></td>
                                <td><?php echo htmlspecialchars($wd['username'] ?? 'N/A'); ?></td>
                                <td>$<?php echo number_format($wd['amount'], 2); ?></td>
                                <td>
                                    <small><?php echo htmlspecialchars(substr($wd['wallet_address'] ?? '', 0, 16)); ?>...</small>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $wd['status']; ?>">
                                        <?php echo ucfirst($wd['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($wd['tx_hash']): ?>
                                        <a href="https://tronscan.org/#/transaction/<?php echo $wd['tx_hash']; ?>" 
                                           target="_blank" class="tx-link">
                                            <?php echo substr($wd['tx_hash'], 0, 10); ?>...
                                        </a>
                                    <?php else: ?>
                                        <em>N/A</em>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y H:i', strtotime($wd['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($wd['status'] == 'pending'): ?>
                                            <div class="action-btn btn-success" 
                                                 onclick="openUpdateModal(<?php echo $wd['withdraw_id']; ?>, 'approve')"
                                                 title="Approve">
                                                <i class="fas fa-check"></i>
                                            </div>
                                            <div class="action-btn btn-danger" 
                                                 onclick="openUpdateModal(<?php echo $wd['withdraw_id']; ?>, 'reject')"
                                                 title="Reject">
                                                <i class="fas fa-times"></i>
                                            </div>
                                        <?php else: ?>
                                            <em class="text-muted">Done</em>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="empty-state">
                                No withdrawals yet
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=affiliate-withdrawals&p=<?php echo $page - 1; ?>" class="page-link">Prev</a>
                    <?php endif; ?>
                    <?php for($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=affiliate-withdrawals&p=<?php echo $i; ?>" 
                           class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=affiliate-withdrawals&p=<?php echo $page + 1; ?>" class="page-link">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Update Withdrawal Modal -->
<div id="updateWithdrawalModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Update Withdrawal Status</h3>
            <button class="modal-close" onclick="closeModal()">x</button>
        </div>
        <div class="modal-body">
            <div id="modalInfo">
                <!-- Will be filled by JS -->
            </div>
            <form id="updateWithdrawalForm" onsubmit="return false;">
                <input type="hidden" name="withdraw_id" id="modal_withdraw_id">
                <input type="hidden" name="action" id="modal_action">

                <div class="form-group form-group-hidden" id="txHashGroup">
                    <label>Transaction Hash (TX Hash)</label>
                    <input type="text" name="tx_hash" id="modal_tx_hash" class="form-control" 
                           placeholder="e.g. 0x123abc...">
                    <small class="text-muted">Only required when approving</small>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-danger" onclick="closeModal()">Cancel</button>
            <button class="btn btn-primary" onclick="submitUpdateWithdrawal()">Confirm</button>
        </div>
    </div>
</div>

<script>
// Global
let currentWithdrawal = null;

function openUpdateModal(withdrawId, action) {
    currentWithdrawal = { withdraw_id: withdrawId, action: action };

    // Fill form
    document.getElementById('modal_withdraw_id').value = withdrawId;
    document.getElementById('modal_action').value = action;

    // Update modal title & info
    const actionText = action === 'approve' ? 'Approve' : 'Reject';
    const color = action === 'approve' ? '#28a745' : '#dc3545';
    document.querySelector('#updateWithdrawalModal .modal-header h3').innerHTML = 
        `<span style="color:${color}">${actionText} Withdrawal #${withdrawId}</span>`;

    // Show/hide TX Hash field
    const txGroup = document.getElementById('txHashGroup');
    if (action === 'approve') {
        txGroup.style.display = 'block';
        document.getElementById('modal_tx_hash').focus();
    } else {
        txGroup.style.display = 'none';
        document.getElementById('modal_tx_hash').value = '';
    }

    // Load withdrawal info
    fetch('api/get_withdrawal.php?id=' + withdrawId)
        .then(r => r.json())
        .then(d => {
            if (d.success && d.withdrawal) {
                const w = d.withdrawal;
                document.getElementById('modalInfo').innerHTML = `
                    <div style="background:#f8f9fa; padding:15px; border-radius:6px; margin-bottom:15px; font-size:14px;">
                        <p><strong>User:</strong> ${w.username || 'N/A'}</p>
                        <p><strong>Amount:</strong> $${parseFloat(w.amount).toFixed(2)}</p>
                        <p><strong>Wallet:</strong> <code>${w.wallet_address}</code></p>
                        <p><strong>Current Status:</strong> 
                            <span class="status-badge status-${w.status}">${w.status}</span>
                        </p>
                    </div>
                `;
            }
        });

    openModal('updateWithdrawalModal');
}

function submitUpdateWithdrawal() {
    const form = document.getElementById('updateWithdrawalForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    // === VALIDATE ===
    if (!data.withdraw_id || !data.action) {
        alert('Missing data. Please try again.');
        return;
    }

    const status = data.action === 'approve' ? 'approved' : 'rejected';

    // Validate TX Hash nếu approve
    if (status === 'approved') {
        const tx = data.tx_hash?.trim();
        if (!tx) {
            alert('Please enter TX Hash when approving.');
            return;
        }
        if (!/^[a-fA-F0-9]{64}$/.test(tx)) {
            alert('Invalid TX Hash! Must be 64 hex characters (0-9, a-f).');
            return;
        }
    }

    // Confirm
    const confirmMsg = status === 'approved'
        ? `Approve withdrawal #${data.withdraw_id}?\nTX Hash: ${data.tx_hash}`
        : `Reject withdrawal #${data.withdraw_id}?`;
    if (!confirm(confirmMsg)) return;

    // === GỬI ĐÚNG TRƯỜNG: status, withdraw_id, tx_hash ===
    fetch('api/update_withdrawal.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            withdraw_id: parseInt(data.withdraw_id),
            status: status,                    // approved / rejected
            tx_hash: status === 'approved' ? data.tx_hash.trim() : null
        })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            alert('Success! Withdrawal ' + status + '.');
            closeModal();
            location.reload();
        } else {
            alert('Error: ' + d.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Network error. Please try again.');
    });
}
function openModal(id) {
    document.getElementById(id).style.display = 'block';
}
function closeModal() {
    document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
}
</script>

<style>
.tx-link { color: #007bff; text-decoration: none; }
.tx-link:hover { text-decoration: underline; }
.modal-content { max-width: 500px; }
#modalInfo code { background: #eee; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
</style>