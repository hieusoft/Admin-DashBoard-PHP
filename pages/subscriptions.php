<?php
require_once __DIR__ . '/../config/db.php';
$conn = getDBConnection();

$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$search = trim($_GET['search'] ?? '');
$searchParam = $search !== '' ? "%$search%" : '';

// Xây dựng điều kiện tìm kiếm
$whereClause = '';
$countParams = [];
$countTypes = '';
$subscriptionsParams = [];
$subscriptionsTypes = '';

if ($search !== '') {
    $whereClause = "WHERE (s.sub_id LIKE ? OR s.user_id LIKE ? OR u.username LIKE ? OR s.status LIKE ?)";
    $countParams = [$searchParam, $searchParam, $searchParam, $searchParam];
    $countTypes = 'ssss';
    $subscriptionsParams = [$searchParam, $searchParam, $searchParam, $searchParam];
    $subscriptionsTypes = 'ssss';
}

// Tổng số subscriptions - Prepared statement với search
$countQuery = "SELECT COUNT(*) as total FROM subscriptions s LEFT JOIN users u ON s.user_id = u.user_id $whereClause";
$stmt = $conn->prepare($countQuery);
if ($search !== '') {
    $stmt->bind_param($countTypes, ...$countParams);
}
$stmt->execute();
$totalResult = $stmt->get_result();
$totalSubs = $totalResult->fetch_assoc()['total'];
$totalPages = max(1, ceil($totalSubs / $perPage));

// Lấy danh sách subscriptions - Prepared statement + chỉ SELECT columns cần thiết
$subscriptionsQuery = "
    SELECT s.sub_id, s.user_id, s.start_date, s.end_date, s.status, 
           s.trial, s.created_at, u.username
    FROM subscriptions s
    LEFT JOIN users u ON s.user_id = u.user_id
    $whereClause
    ORDER BY s.created_at DESC
    LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($subscriptionsQuery);

if ($search !== '') {
    $bindParams = array_merge($subscriptionsParams, [$perPage, $offset]);
    $bindTypes = $subscriptionsTypes . 'ii';
    $stmt->bind_param($bindTypes, ...$bindParams);
} else {
    $stmt->bind_param('ii', $perPage, $offset);
}
$stmt->execute();
$subscriptions = $stmt->get_result();

// Get users list for dropdown - Chỉ lấy 100 users gần nhất thay vì tất cả
$usersQuery = "SELECT user_id, username FROM users ORDER BY user_id DESC LIMIT 100";
$users = $conn->query($usersQuery);

// Store users for edit modal (need to fetch all before closing connection)
$usersArray = [];
if ($users && $users->num_rows > 0) {
    while($u = $users->fetch_assoc()) {
        $usersArray[] = $u;
    }
}

closeDBConnection($conn);
?>

<div id="subscriptions" class="page <?php echo ($currentPage == 'subscriptions') ? 'active' : ''; ?>">
    <div class="card">
        <div class="card-header">
            <h2>Quản lý Subscriptions</h2>
            <button class="btn btn-primary" onclick="openModal('addSubscriptionModal')">
                <i class="fas fa-plus"></i> Thêm Subscription
            </button>
        </div>

        <!-- Search Bar -->
        <div class="card-body" style="padding-bottom: 0;">
            <form method="get" id="searchForm">
                <input type="hidden" name="page" value="subscriptions">
                <div class="search-bar">
                    <input
                        type="text"
                        name="search"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search by Subscription ID, User ID, Username, or Status..."
                        class="search-input"
                    >
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if ($search !== ''): ?>
                        <a href="?page=subscriptions" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </div>
                <?php if ($search !== ''): ?>
                    <small class="search-info">
                        Found <strong><?php echo $totalSubs; ?></strong> subscription(s)
                    </small>
                <?php endif; ?>
            </form>
        </div>

        <div class="card-body">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Trial</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($subscriptions && $subscriptions->num_rows > 0): ?>
                        <?php while($sub = $subscriptions->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $sub['sub_id']; ?></td>
                                <td><?php echo htmlspecialchars($sub['username'] ?? 'N/A'); ?></td>
                                <td><?php echo $sub['start_date'] ? date('d/m/Y H:i', strtotime($sub['start_date'])) : 'N/A'; ?></td>
                                <td><?php echo $sub['end_date'] ? date('d/m/Y H:i', strtotime($sub['end_date'])) : 'N/A'; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $sub['status']; ?>">
                                        <?php echo ucfirst($sub['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $sub['trial'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($sub['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <div class="action-btn btn-success" 
                                             onclick="viewSubscriptionDetails(<?php echo $sub['sub_id']; ?>)"
                                             title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </div>
                                        <div class="action-btn btn-info" 
                                             onclick="editSubscription(<?php echo $sub['sub_id']; ?>)"
                                             title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </div>
                                        <div class="action-btn btn-danger"
                                             onclick="deleteSubscription(<?php echo $sub['sub_id']; ?>)"
                                             title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <i class="fas fa-calendar-check"></i>
                                    <p>
                                        <?php if ($search !== ''): ?>
                                            No subscriptions found for "<strong><?php echo htmlspecialchars($search); ?></strong>"
                                        <?php else: ?>
                                            Chưa có subscription nào
                                        <?php endif; ?>
                                    </p>
                                </td>
                            </tr>
                        <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php
                    $baseUrl = '?page=subscriptions' . ($search !== '' ? '&search=' . urlencode($search) : '');
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    ?>
                    <?php if ($page > 1): ?>
                        <a href="<?php echo $baseUrl; ?>&p=1" class="page-link">First</a>
                        <a href="<?php echo $baseUrl; ?>&p=<?php echo $page - 1; ?>" class="page-link">Prev</a>
                    <?php endif; ?>
                    <?php for($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="<?php echo $baseUrl; ?>&p=<?php echo $i; ?>" 
                           class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?php echo $baseUrl; ?>&p=<?php echo $page + 1; ?>" class="page-link">Next</a>
                        <a href="<?php echo $baseUrl; ?>&p=<?php echo $totalPages; ?>" class="page-link">Last</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Subscription Details Modal -->
<div id="subscriptionDetailsModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3>Subscription Details</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="detailsContent">
                <div class="loading">Đang tải...</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" onclick="closeModal()">Đóng</button>
        </div>
    </div>
</div>

<!-- Edit Subscription Detail Modal -->
<div id="editSubscriptionDetailModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Sửa Subscription Detail</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editSubscriptionDetailForm" method="post" onsubmit="return false;">
                <input type="hidden" name="detail_id" id="edit_detail_id">
                <div class="form-group">
                    <label>Detail ID</label>
                    <input type="number" id="edit_display_detail_id" class="form-control disabled-input" disabled>
                </div>
                <div class="form-group">
                    <label>Plan</label>
                    <select name="plan_id" id="edit_detail_plan_id" class="form-control" required>
                        <option value="">-- Đang tải... --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Payment ID</label>
                    <select name="payment_id" id="edit_detail_payment_id" class="form-control">
                        <option value="">-- Không có / Đang tải... --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Expired At</label>
                    <input type="datetime-local" name="expired_at" id="edit_detail_expired_at" class="form-control">
                </div>
                <div class="form-group">
                    <label>Renewed</label>
                    <select name="renewed" id="edit_detail_renewed" class="form-control">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" onclick="closeModal()">Hủy</button>
            <button type="button" onclick="document.getElementById('editSubscriptionDetailForm').dispatchEvent(new Event('submit'));" class="btn btn-primary">Cập nhật</button>
        </div>
    </div>
</div>

<!-- Add Subscription Modal -->
<div id="addSubscriptionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Thêm Subscription</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addSubscriptionForm" method="post" onsubmit="return false;">
                <div class="form-group">
                    <label>User</label>
                    <select name="user_id" class="form-control" required>
                        <option value="">-- Chọn User --</option>
                        <?php if (!empty($usersArray)): ?>
                            <?php foreach($usersArray as $user): ?>
                                <option value="<?php echo $user['user_id']; ?>">
                                    <?php echo htmlspecialchars($user['username'] ?? 'User #' . $user['user_id']); ?> (#<?php echo $user['user_id']; ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="datetime-local" name="start_date" class="form-control">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="datetime-local" name="end_date" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="pending">Pending</option>
                                <option value="active">Active</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label>Trial</label>
                            <select name="trial" class="form-control">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" onclick="closeModal()">Hủy</button>
            <button type="button" onclick="document.getElementById('addSubscriptionForm').dispatchEvent(new Event('submit'));" class="btn btn-primary">Lưu</button>
        </div>
    </div>
</div>

<!-- Edit Subscription Modal -->
<div id="editSubscriptionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Sửa Subscription</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editSubscriptionForm" method="post" onsubmit="return false;">
                <input type="hidden" name="sub_id" id="edit_sub_id">
                <div class="form-group">
                    <label>Subscription ID</label>
                    <input type="number" id="edit_display_sub_id" class="form-control disabled-input" disabled>
                </div>
                <div class="form-group">
                    <label>User</label>
                    <select name="user_id" id="edit_user_id" class="form-control" required>
                        <option value="">-- Chọn User --</option>
                        <?php if (!empty($usersArray)): ?>
                            <?php foreach($usersArray as $u): ?>
                                <option value="<?php echo $u['user_id']; ?>">
                                    <?php echo htmlspecialchars($u['username'] ?? 'User #' . $u['user_id']); ?> (#<?php echo $u['user_id']; ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="datetime-local" name="start_date" id="edit_start_date" class="form-control">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="datetime-local" name="end_date" id="edit_end_date" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" id="edit_status" class="form-control">
                                <option value="pending">Pending</option>
                                <option value="active">Active</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label>Trial</label>
                            <select name="trial" id="edit_trial" class="form-control">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" onclick="closeModal()">Hủy</button>
            <button type="button" onclick="document.getElementById('editSubscriptionForm').dispatchEvent(new Event('submit'));" class="btn btn-primary">Cập nhật</button>
        </div>
    </div>
</div>

<script>
function viewSubscriptionDetails(subId) {
    // Open modal
    openModal('subscriptionDetailsModal');
    
    // Show loading
    document.getElementById('detailsContent').innerHTML = '<div class="loading">Đang tải...</div>';
    
    // Load subscription info
    fetch('api/get_subscription.php?id=' + subId)
        .then(response => response.json())
        .then(subData => {
            // Load subscription details
            fetch('api/get_subscription_details.php?sub_id=' + subId)
                .then(response => response.json())
                .then(detailsData => {
                    let html = '';
                    
                    if (subData.success && subData.subscription) {
                        const sub = subData.subscription;
                        
                        // Subscription info section
                        html += '<div class="card" style="margin-bottom: 20px;">';
                        html += '<div class="card-header"><h4>Thông tin Subscription</h4></div>';
                        html += '<div class="card-body">';
                        html += '<table style="width: 100%;">';
                        html += '<tr><td style="width: 150px; font-weight: bold;">Subscription ID:</td><td>#' + sub.sub_id + '</td></tr>';
                        html += '<tr><td style="font-weight: bold;">User ID:</td><td>#' + sub.user_id + '</td></tr>';
                        html += '<tr><td style="font-weight: bold;">Start Date:</td><td>' + (sub.start_date ? new Date(sub.start_date).toLocaleString('vi-VN') : 'N/A') + '</td></tr>';
                        html += '<tr><td style="font-weight: bold;">End Date:</td><td>' + (sub.end_date ? new Date(sub.end_date).toLocaleString('vi-VN') : 'N/A') + '</td></tr>';
                        html += '<tr><td style="font-weight: bold;">Status:</td><td><span class="status-badge status-' + sub.status + '">' + sub.status.charAt(0).toUpperCase() + sub.status.slice(1) + '</span></td></tr>';
                        html += '<tr><td style="font-weight: bold;">Trial:</td><td>' + (sub.trial ? 'Yes' : 'No') + '</td></tr>';
                        html += '<tr><td style="font-weight: bold;">Created:</td><td>' + (sub.created_at ? new Date(sub.created_at).toLocaleString('vi-VN') : 'N/A') + '</td></tr>';
                        html += '</table>';
                        html += '</div></div>';
                    }
                    
                    // Details section
                    html += '<div class="card">';
                    html += '<div class="card-header"><h4>Chi tiết Plans (' + (detailsData.success ? detailsData.details.length : 0) + ')</h4></div>';
                    html += '<div class="card-body">';
                    
                    if (detailsData.success && detailsData.details && detailsData.details.length > 0) {
                        html += '<table style="width: 100%; margin-top: 10px;">';
                        html += '<thead><tr>';
                        html += '<th style="padding: 8px; border-bottom: 1px solid #ddd;">Detail ID</th>';
                        html += '<th style="padding: 8px; border-bottom: 1px solid #ddd;">Plan</th>';
                        html += '<th style="padding: 8px; border-bottom: 1px solid #ddd;">Payment ID</th>';
                        html += '<th style="padding: 8px; border-bottom: 1px solid #ddd;">Activated At</th>';
                        html += '<th style="padding: 8px; border-bottom: 1px solid #ddd;">Expired At</th>';
                        html += '<th style="padding: 8px; border-bottom: 1px solid #ddd;">Renewed</th>';
                        html += '<th style="padding: 8px; border-bottom: 1px solid #ddd;">Hành động</th>';
                        html += '</tr></thead>';
                        html += '<tbody>';
                        
                        detailsData.details.forEach(detail => {
                            html += '<tr>';
                            html += '<td style="padding: 8px; border-bottom: 1px solid #eee;">#' + detail.detail_id + '</td>';
                            html += '<td style="padding: 8px; border-bottom: 1px solid #eee;">' + (detail.plan_name || 'N/A') + '</td>';
                            html += '<td style="padding: 8px; border-bottom: 1px solid #eee;">' + (detail.payment_id ? '#' + detail.payment_id : 'N/A') + '</td>';
                            html += '<td style="padding: 8px; border-bottom: 1px solid #eee;">' + (detail.activated_at ? new Date(detail.activated_at).toLocaleString('vi-VN') : 'N/A') + '</td>';
                            html += '<td style="padding: 8px; border-bottom: 1px solid #eee;">' + (detail.expired_at ? new Date(detail.expired_at).toLocaleString('vi-VN') : 'N/A') + '</td>';
                            html += '<td style="padding: 8px; border-bottom: 1px solid #eee;"><span class="status-badge ' + (detail.renewed ? 'status-success' : 'status-pending') + '">' + (detail.renewed ? 'Yes' : 'No') + '</span></td>';
                            html += '<td style="padding: 8px; border-bottom: 1px solid #eee;">';
                            html += '<div class="action-buttons">';
                            html += '<div class="action-btn" style="background-color: var(--info-color); cursor: pointer;" onclick="editSubscriptionDetail(' + detail.detail_id + ', ' + subId + ')" title="Sửa">';
                            html += '<i class="fas fa-edit"></i>';
                            html += '</div>';
                            html += '</div>';
                            html += '</td>';
                            html += '</tr>';
                        });
                        
                        html += '</tbody></table>';
                    } else {
                        html += '<div class="empty-state" style="padding: 40px;">';
                        html += '<i class="fas fa-info-circle"></i>';
                        html += '<p>Chưa có detail nào cho subscription này</p>';
                        html += '</div>';
                    }
                    
                    html += '</div></div>';
                    
                    // Store subId globally for refresh after edit
                    window.currentSubId = subId;
                    
                    document.getElementById('detailsContent').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error loading details:', error);
                    document.getElementById('detailsContent').innerHTML = '<div style="padding: 20px; color: red;">Có lỗi xảy ra khi tải details</div>';
                });
        })
        .catch(error => {
            console.error('Error loading subscription:', error);
            document.getElementById('detailsContent').innerHTML = '<div style="padding: 20px; color: red;">Có lỗi xảy ra khi tải thông tin subscription</div>';
        });
}

function editSubscription(subId) {
    // Load subscription data
    fetch('api/get_subscription.php?id=' + subId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.subscription) {
                const sub = data.subscription;
                
                // Populate form fields
                document.getElementById('edit_sub_id').value = sub.sub_id;
                document.getElementById('edit_display_sub_id').value = sub.sub_id;
                document.getElementById('edit_user_id').value = sub.user_id || '';
                
                // Format datetime-local input (convert from MySQL datetime format)
                if (sub.start_date) {
                    const startDate = new Date(sub.start_date);
                    document.getElementById('edit_start_date').value = startDate.toISOString().slice(0, 16);
                } else {
                    document.getElementById('edit_start_date').value = '';
                }
                
                if (sub.end_date) {
                    const endDate = new Date(sub.end_date);
                    document.getElementById('edit_end_date').value = endDate.toISOString().slice(0, 16);
                } else {
                    document.getElementById('edit_end_date').value = '';
                }
                
                document.getElementById('edit_status').value = sub.status || 'pending';
                document.getElementById('edit_trial').value = sub.trial || 0;
                
                // Open modal
                openModal('editSubscriptionModal');
            } else {
                alert('Không thể tải thông tin subscription: ' + (data.message || 'Lỗi không xác định'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi tải thông tin subscription');
        });
}

function deleteSubscription(subId) {
    if (confirm('Bạn có chắc chắn muốn xóa subscription này?')) {
        fetch('api/delete_subscription.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({sub_id: subId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi xóa subscription');
        });
    }
}

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Handle Add Subscription Form
    const addForm = document.getElementById('addSubscriptionForm');
    if (addForm) {
        addForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const formData = new FormData(addForm);
            const data = Object.fromEntries(formData);
            
            // Convert empty datetime strings to null
            if (data.start_date === '') data.start_date = null;
            if (data.end_date === '') data.end_date = null;
            
            // Convert to integers
            data.user_id = parseInt(data.user_id);
            data.trial = parseInt(data.trial);
            
            try {
                const response = await fetch('api/add_subscription.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closeModal();
                    location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Có lỗi xảy ra'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi xử lý yêu cầu');
            }
        });
    }
    
    // Handle Edit Subscription Form
    const editForm = document.getElementById('editSubscriptionForm');
    if (editForm) {
        editForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const formData = new FormData(editForm);
            const data = Object.fromEntries(formData);
            
            // Convert empty datetime strings to null
            if (data.start_date === '') data.start_date = null;
            if (data.end_date === '') data.end_date = null;
            
            // Convert to integers
            data.sub_id = parseInt(data.sub_id);
            data.user_id = parseInt(data.user_id);
            data.trial = parseInt(data.trial);
            
            try {
                const response = await fetch('api/update_subscription.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closeModal();
                    location.reload();
                } else {
                    alert('Error: ' + (result.message || 'Có lỗi xảy ra'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi xử lý yêu cầu');
            }
        });
    }
    
    // Handle Edit Subscription Detail Form
    const editDetailForm = document.getElementById('editSubscriptionDetailForm');
    if (editDetailForm) {
        editDetailForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const formData = new FormData(editDetailForm);
            const data = Object.fromEntries(formData);
            
            // Convert empty datetime strings to null
            if (data.expired_at === '') data.expired_at = null;
            
            // Convert to integers
            data.detail_id = parseInt(data.detail_id);
            data.plan_id = parseInt(data.plan_id);
            data.renewed = parseInt(data.renewed);
            if (data.payment_id === '') {
                data.payment_id = null;
            } else {
                data.payment_id = parseInt(data.payment_id);
            }
            
            try {
                const response = await fetch('api/update_subscription_detail.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closeModal();
                    // Reload details if modal is open
                    if (window.currentSubId) {
                        viewSubscriptionDetails(window.currentSubId);
                    } else {
                        location.reload();
                    }
                } else {
                    alert('Error: ' + (result.message || 'Có lỗi xảy ra'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi xử lý yêu cầu');
            }
        });
    }
});

function editSubscriptionDetail(detailId, subId) {
    // Load detail data
    fetch('api/get_subscription_detail.php?id=' + detailId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.detail) {
                const detail = data.detail;
                
                // Populate form fields
                document.getElementById('edit_detail_id').value = detail.detail_id;
                document.getElementById('edit_display_detail_id').value = detail.detail_id;
                
                // Load plans dropdown
                fetch('api/get_plans_list.php')
                    .then(r => r.json())
                    .then(plansData => {
                        const planSelect = document.getElementById('edit_detail_plan_id');
                        planSelect.innerHTML = '<option value="">-- Chọn Plan --</option>';
                        
                        if (plansData.success && plansData.plans) {
                            plansData.plans.forEach(plan => {
                                const option = document.createElement('option');
                                option.value = plan.plan_id;
                                option.textContent = plan.name + ' (#' + plan.plan_id + ')';
                                if (plan.plan_id == detail.plan_id) {
                                    option.selected = true;
                                }
                                planSelect.appendChild(option);
                            });
                        }
                    });
                
                // Load payments dropdown
                fetch('api/get_payments_list.php')
                    .then(r => r.json())
                    .then(paymentsData => {
                        const paymentSelect = document.getElementById('edit_detail_payment_id');
                        paymentSelect.innerHTML = '<option value="">-- Không có Payment --</option>';
                        
                        if (paymentsData.success && paymentsData.payments) {
                            paymentsData.payments.forEach(payment => {
                                const option = document.createElement('option');
                                option.value = payment.payment_id;
                                option.textContent = '#' + payment.payment_id + ' - ' + payment.amount + ' ' + payment.currency + ' (' + payment.status + ')';
                                if (payment.payment_id == detail.payment_id) {
                                    option.selected = true;
                                }
                                paymentSelect.appendChild(option);
                            });
                        }
                    });
                
                // Set expired_at
                if (detail.expired_at) {
                    const expiredDate = new Date(detail.expired_at);
                    document.getElementById('edit_detail_expired_at').value = expiredDate.toISOString().slice(0, 16);
                } else {
                    document.getElementById('edit_detail_expired_at').value = '';
                }
                
                // Set renewed
                document.getElementById('edit_detail_renewed').value = detail.renewed || 0;
                
                // Store subId for refresh
                window.currentSubId = subId;
                
                // Open modal
                openModal('editSubscriptionDetailModal');
            } else {
                alert('Không thể tải thông tin detail: ' + (data.message || 'Lỗi không xác định'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi tải thông tin detail');
        });
}
</script>

