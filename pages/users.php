<?php
require_once __DIR__ . '/../config/db.php';

// === Khởi tạo ===
$conn = null;
$users = null;
$totalUsers = 0;
$totalPages = 0;
$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;
$search = trim($_GET['search'] ?? '');
$searchParam = $search !== '' ? "%$search%" : '';
$error = null;

try {
    $conn = getDBConnection();

    // Kiểm tra bảng tồn tại
    $tableCheck = $conn->query("SHOW TABLES LIKE 'users'");
    if ($tableCheck->num_rows === 0) {
        $error = "Bảng 'users' chưa tồn tại. Vui lòng import SQL schema.";
    } else {
        // === Xây dựng điều kiện tìm kiếm ===
        $whereClause = '';
        $countParams = [];
        $countTypes = '';
        $userParams = [];
        $userTypes = '';

        if ($search !== '') {
            $whereClause = "WHERE (u.user_id LIKE ? OR u.username LIKE ?)";
            $countParams = [$searchParam, $searchParam];
            $countTypes = 'ss';
            $userParams = [$searchParam, $searchParam];
            $userTypes = 'ss';
        }

        // === Đếm tổng ===
        $countQuery = "SELECT COUNT(*) as total FROM users u $whereClause";
        $stmt = $conn->prepare($countQuery);
        if ($search !== '') {
            $stmt->bind_param($countTypes, ...$countParams);
        }
        $stmt->execute();
        $totalUsers = $stmt->get_result()->fetch_assoc()['total'];
        $totalPages = max(1, ceil($totalUsers / $perPage));

        // === Lấy danh sách users ===
        $usersQuery = "
            SELECT u.*,
                   (SELECT COUNT(*) FROM users WHERE ref_by = u.user_id) as referrals_count
            FROM users u
            $whereClause
            ORDER BY COALESCE(u.created_at, '1970-01-01') DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = $conn->prepare($usersQuery);

        // GỘP TẤT CẢ THAM SỐ VÀO MỘT MẢNG RỒI UNPACK
        if ($search !== '') {
            $bindParams = array_merge($userParams, [$perPage, $offset]);
            $bindTypes = $userTypes . 'ii';
            $stmt->bind_param($bindTypes, ...$bindParams);
        } else {
            $stmt->bind_param('ii', $perPage, $offset);
        }

        $stmt->execute();
        $users = $stmt->get_result();
    }
} catch (Exception $e) {
    $error = "Lỗi kết nối: " . htmlspecialchars($e->getMessage());
}

closeDBConnection($conn);
?>

<!-- === GIAO DIỆN === -->
<div id="users" class="page <?php echo ($currentPage == 'users') ? 'active' : ''; ?>">
    <div class="card">
        <div class="card-header">
            <h2>User Management</h2>
            <button class="btn btn-primary" onclick="openModal('addUserModal')">
                Add User
            </button>
        </div>

        <!-- Search Bar -->
        <div class="card-body" style="padding-bottom: 0;">
            <form method="get" id="searchForm">
                <input type="hidden" name="page" value="users">
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 15px;">
                    <input
                        type="text"
                        name="search"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search by User ID or Username..."
                        style="flex: 1; min-width: 250px; padding: 10px 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px;"
                    >
                    <button type="submit" class="btn btn-primary" style="padding: 10px 18px;">
                        Search
                    </button>
                    <?php if ($search !== ''): ?>
                        <a href="?page=users" class="btn btn-secondary" style="padding: 10px 16px;">
                            Clear Filter
                        </a>
                    <?php endif; ?>
                </div>
                <?php if ($search !== ''): ?>
                    <small style="color: #555; margin-top: 4px; display: block;">
                        Found <strong><?php echo $totalUsers; ?></strong> user(s)
                    </small>
                <?php endif; ?>
            </form>
        </div>

        <!-- User List -->
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-warning">
                    <strong>Error:</strong> <?php echo $error; ?>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Language</th>
                            <th>Ref By</th>
                            <th>Wallet</th>
                            <th>KOL Status</th>
                            <th>Commission</th>
                            <th>Referrals</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users && $users->num_rows > 0): ?>
                            <?php while ($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?php echo $user['user_id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></td>
                                    <td><?php echo strtoupper($user['language'] ?? 'en'); ?></td>
                                    <td><?php echo $user['ref_by'] ? '#' . $user['ref_by'] : 'N/A'; ?></td>
                                    <td>
                                        <small>
                                            <?php 
                                            $wallet = $user['wallet_address'] ?? '';
                                            echo htmlspecialchars(substr($wallet, 0, 12));
                                            echo $wallet ? '...' : '';
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo str_replace('_', '-', $user['verified_kol'] ?? 'not-submitted'); ?>">
                                            <?php 
                                            $status = $user['verified_kol'] ?? 'not_submitted';
                                            echo ucwords(str_replace('_', ' ', $status));
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($user['commission_percent'] ?? 30, 2); ?>%</td>
                                    <td><strong><?php echo $user['referrals_count'] ?? 0; ?></strong></td>
                                    <td>
                                        <?php echo $user['created_at']
                                            ? date('M j, Y H:i', strtotime($user['created_at']))
                                            : 'N/A'; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <div class="action-btn"  style="background-color: var(--success-color);"
                                                 onclick="viewUserReferrals(<?php echo $user['user_id']; ?>)"
                                                 title="View Referrals">
                                                 <i class="fas fa-eye"></i>
                                            </div>
                                            <div class="action-btn"  style="background-color: var(--info-color);" 
                                                 onclick="editUser(<?php echo $user['user_id']; ?>)"
                                                 title="Edit">
                                                 <i class="fas fa-edit"></i>
                                            </div>
                                            <div class="action-btn" style="background-color: var(--danger-color);"
                                                 onclick="deleteUser(<?php echo $user['user_id']; ?>)"
                                                 title="Delete">
                                                 <i class="fas fa-trash"></i>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="empty-state">
                                    No users found
                                    <?php if ($search !== ''): ?>
                                        <br><small>No results for "<strong><?php echo htmlspecialchars($search); ?></strong>"</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php
                        $baseUrl = '?page=users' . ($search !== '' ? '&search=' . urlencode($search) : '');
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        ?>
                        <?php if ($page > 1): ?>
                            <a href="<?php echo $baseUrl; ?>&p=1" class="page-link">First</a>
                            <a href="<?php echo $baseUrl; ?>&p=<?php echo $page - 1; ?>" class="page-link">Prev</a>
                        <?php endif; ?>
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
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
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- User Referrals Modal -->
<div id="userReferralsModal" class="modal">
    <div class="modal-content" style="width: 800px; max-width: 95%;">
        <div class="modal-header">
            <h3>Affiliate Referrals</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div id="referralsContent">
                <div class="loading">Đang tải...</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" onclick="closeModal()">Đóng</button>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Thêm User</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addUserForm" method="post" onsubmit="return false;">
                <div class="form-group">
                    <label>User ID</label>
                    <input type="number" name="user_id" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Language</label>
                    <select name="language" class="form-control">
                        <option value="en">English</option>
                        <option value="vi">Tiếng Việt</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Ref By (User ID)</label>
                    <input type="number" name="ref_by" class="form-control">
                </div>
                <div class="form-group">
                    <label>Wallet Address</label>
                    <input type="text" name="wallet_address" class="form-control">
                </div>
                <div class="form-group">
                    <label>KOL Status</label>
                    <select name="verified_kol" class="form-control">
                        <option value="not_submitted">Not Submitted</option>
                        <option value="under_review">Under Review</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Commission Percent</label>
                    <input type="number" name="commission_percent" class="form-control" step="0.01" value="30.00">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" onclick="closeModal()">Hủy</button>
            <button type="button" onclick="document.getElementById('addUserForm').dispatchEvent(new Event('submit'));" class="btn btn-primary">Lưu</button>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Sửa User</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editUserForm" method="post" onsubmit="return false;">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="form-group">
                    <label>User ID</label>
                    <input type="number" id="edit_display_user_id" class="form-control" disabled style="background-color: #f5f5f5;">
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" id="edit_username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Language</label>
                    <select name="language" id="edit_language" class="form-control">
                        <option value="en">English</option>
                        <option value="vi">Tiếng Việt</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Ref By (User ID)</label>
                    <input type="number" name="ref_by" id="edit_ref_by" class="form-control">
                </div>
                <div class="form-group">
                    <label>Wallet Address</label>
                    <input type="text" name="wallet_address" id="edit_wallet_address" class="form-control">
                </div>
                <div class="form-group">
                    <label>KOL Status</label>
                    <select name="verified_kol" id="edit_verified_kol" class="form-control">
                        <option value="not_submitted">Not Submitted</option>
                        <option value="under_review">Under Review</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Commission Percent</label>
                    <input type="number" name="commission_percent" id="edit_commission_percent" class="form-control" step="0.01">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" onclick="closeModal()">Hủy</button>
            <button type="button" onclick="document.getElementById('editUserForm').dispatchEvent(new Event('submit'));" class="btn btn-primary">Cập nhật</button>
        </div>
    </div>
</div>

<script>
function viewUserReferrals(userId) {
    // Store userId for refresh after update
    window.currentUserId = userId;
    
    // Open modal
    openModal('userReferralsModal');
    
    // Show loading
    document.getElementById('referralsContent').innerHTML = '<div class="loading">Đang tải...</div>';
    
    // Load user referrals
    fetch('api/get_user_referrals.php?user_id=' + userId)
        .then(response => response.json())
        .then(data => {
            let html = '';
            
            if (data.success && data.referrals) {
                html += '<div class="card">';
                html += '<div class="card-header">';
                html += '<h4>Danh sách Referrals (Tổng: ' + (data.count || 0) + ')</h4>';
                html += '</div>';
                html += '<div class="card-body">';
                
                if (data.referrals && data.referrals.length > 0) {
                    html += '<table style="width: 100%;">';
                    html += '<thead><tr>';
                    html += '<th style="padding: 8px; border-bottom: 1px solid #ddd;">ID</th>';
                    html += '<th style="padding: 8px; border-bottom: 1px solid #ddd;">Referrer</th>';
                    html += '<th style="padding: 8px; border-bottom: 1px solid #ddd;">Referred</th>';
                    html += '<th style="padding: 8px; border-bottom: 1px solid #ddd;">Commission (USD)</th>';
                    html += '<th style="padding: 8px; border-bottom: 1px solid #ddd;">Status</th>';
                    html += '<th style="padding: 8px; border-bottom: 1px solid #ddd;">Ngày tạo</th>';
                    html += '<th style="padding: 8px; border-bottom: 1px solid #ddd;">Hành động</th>';
                    html += '</tr></thead>';
                    html += '<tbody>';
                    
                    data.referrals.forEach(ref => {
                        html += '<tr>';
                        html += '<td style="padding: 8px; border-bottom: 1px solid #eee;">#' + ref.ref_id + '</td>';
                        html += '<td style="padding: 8px; border-bottom: 1px solid #eee;">' + (ref.referrer_name || 'N/A') + '</td>';
                        html += '<td style="padding: 8px; border-bottom: 1px solid #eee;">' + (ref.referred_name || 'N/A') + '</td>';
                        html += '<td style="padding: 8px; border-bottom: 1px solid #eee;">$' + parseFloat(ref.commission_usd || 0).toFixed(2) + '</td>';
                        html += '<td style="padding: 8px; border-bottom: 1px solid #eee;">';
                        html += '<span class="status-badge status-' + ref.status + '">' + ref.status.charAt(0).toUpperCase() + ref.status.slice(1) + '</span>';
                        html += '</td>';
                        html += '<td style="padding: 8px; border-bottom: 1px solid #eee;">' + (ref.created_at ? new Date(ref.created_at).toLocaleString('vi-VN') : 'N/A') + '</td>';
                        html += '<td style="padding: 8px; border-bottom: 1px solid #eee;">';
                        html += '<div class="action-buttons">';
                        html += '<div class="action-btn" style="background-color: var(--success-color); cursor: pointer;" onclick="updateReferralStatus(' + ref.ref_id + ', \'approved\')" title="Approve">';
                        html += '<i class="fas fa-check"></i>';
                        html += '</div>';
                        html += '<div class="action-btn" style="background-color: var(--warning-color); cursor: pointer;" onclick="updateReferralStatus(' + ref.ref_id + ', \'pending\')" title="Pending">';
                        html += '<i class="fas fa-clock"></i>';
                        html += '</div>';
                        html += '</div>';
                        html += '</td>';
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table>';
                } else {
                    html += '<div class="empty-state" style="padding: 40px;">';
                    html += '<i class="fas fa-handshake"></i>';
                    html += '<p>User này chưa có referral nào</p>';
                    html += '</div>';
                }
                
                html += '</div></div>';
            } else {
                html += '<div style="padding: 20px; color: red;">';
                html += 'Không thể tải referrals: ' + (data.message || 'Lỗi không xác định');
                html += '</div>';
            }
            
            document.getElementById('referralsContent').innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading referrals:', error);
            document.getElementById('referralsContent').innerHTML = '<div style="padding: 20px; color: red;">Có lỗi xảy ra khi tải referrals</div>';
        });
}

function updateReferralStatus(refId, status) {
    fetch('api/update_referral.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ref_id: refId, status: status})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Find userId from current modal
            const urlParams = new URLSearchParams(window.location.search);
            const currentUserId = window.currentUserId || null;
            if (currentUserId) {
                viewUserReferrals(currentUserId);
            } else {
                location.reload();
            }
        } else {
            alert('Error: ' + (data.message || 'Có lỗi xảy ra'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi cập nhật status');
    });
}

function editUser(userId) {
    // Load user data
    fetch('api/get_user.php?id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.user) {
                const user = data.user;
                
                // Populate form fields
                document.getElementById('edit_user_id').value = user.user_id;
                document.getElementById('edit_display_user_id').value = user.user_id;
                document.getElementById('edit_username').value = user.username || '';
                document.getElementById('edit_language').value = user.language || 'en';
                document.getElementById('edit_ref_by').value = user.ref_by || '';
                document.getElementById('edit_wallet_address').value = user.wallet_address || '';
                document.getElementById('edit_verified_kol').value = user.verified_kol || 'not_submitted';
                document.getElementById('edit_commission_percent').value = user.commission_percent || 30.00;
                
                // Open modal
                openModal('editUserModal');
            } else {
                alert('Không thể tải thông tin user: ' + (data.message || 'Lỗi không xác định'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi tải thông tin user');
        });
}

function deleteUser(userId) {
    if (confirm('Bạn có chắc chắn muốn xóa user này?')) {
        fetch('api/delete_user.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({user_id: userId})
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
            alert('Có lỗi xảy ra khi xóa user');
        });
    }
}

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Handle Add User Form
    const addForm = document.getElementById('addUserForm');
    if (addForm) {
        addForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const formData = new FormData(addForm);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('api/add_user.php', {
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
    
    // Handle Edit User Form
    const editForm = document.getElementById('editUserForm');
    if (editForm) {
        editForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const formData = new FormData(editForm);
            const data = Object.fromEntries(formData);
            
            // Convert empty strings to null for optional fields
            if (data.ref_by === '') data.ref_by = null;
            if (data.wallet_address === '') data.wallet_address = null;
            
            try {
                const response = await fetch('api/update_user.php', {
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
});
</script>

