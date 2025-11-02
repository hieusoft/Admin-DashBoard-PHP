<?php
require_once __DIR__ . '/../config/db.php';
$conn = getDBConnection();

// === HÀM AN TOÀN: ĐẾM SỐ LƯỢNG ===
function safeCount($conn, $query, $msg = "Query failed") {
    $result = $conn->query($query);
    if (!$result) {
        error_log("SQL Error ($msg): " . $conn->error . " | Query: " . $query);
        return 0;
    }
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

// === 1. THỐNG KÊ TỔNG QUAN ===
$stats = [];

// Tổng Users
$stats['users'] = safeCount($conn, "SELECT COUNT(*) as total FROM users", "Count users");

// Tổng Payment thành công
$stats['payments_success'] = safeCount($conn, "SELECT COUNT(*) as total FROM payments WHERE status = 'success'", "Count payments");

// Tổng Withdrawal đang chờ
$stats['withdrawals_pending'] = safeCount($conn, "SELECT COUNT(*) as total FROM affiliate_withdrawals WHERE status = 'pending'", "Count pending withdrawals");

// Tổng gói đang sale
$stats['plans_on_sale'] = safeCount($conn, "
    SELECT COUNT(*) as total 
    FROM subscription_plans 
    WHERE is_active = 1 
      AND sale_percent > 0 
      AND sale_start IS NOT NULL 
      AND sale_end IS NOT NULL 
      AND NOW() BETWEEN sale_start AND sale_end
", "Count sale plans");

// === 2. PAYMENTS THÀNH CÔNG ===
$recentPayments = $conn->query("
    SELECT p.amount, p.currency, u.username, p.created_at
    FROM payments p
    JOIN users u ON p.user_id = u.user_id
    WHERE p.status = 'success'
    ORDER BY p.created_at DESC
    LIMIT 5
");
if (!$recentPayments) {
    error_log("SQL Error (recent payments): " . $conn->error);
    $recentPayments = false;
}

// === 3. RÚT TIỀN ĐANG CHỜ ===
$pendingWithdrawals = $conn->query("
    SELECT aw.withdraw_id, aw.amount, u.username, aw.created_at
    FROM affiliate_withdrawals aw
    JOIN users u ON aw.user_id = u.user_id
    WHERE aw.status = 'pending'
    ORDER BY aw.created_at DESC
    LIMIT 5
");
if (!$pendingWithdrawals) {
    error_log("SQL Error (pending withdrawals): " . $conn->error);
    $pendingWithdrawals = false;
}

// === 4. GÓI ĐANG SALE ===
$plansOnSale = $conn->query("
    SELECT 
        name,
        price,
        sale_percent,
        sale_start,
        sale_end,
        (price * (100 - sale_percent) / 100) AS sale_price
    FROM subscription_plans
    WHERE is_active = 1
      AND sale_percent > 0
      AND sale_start IS NOT NULL
      AND sale_end IS NOT NULL
      AND NOW() BETWEEN sale_start AND sale_end
    ORDER BY sale_end ASC
    LIMIT 10
");
if (!$plansOnSale) {
    error_log("SQL Error (sale plans): " . $conn->error);
    $plansOnSale = false;
}

// === 5. USER KOL ĐANG CHỜ DUYỆT ===
$kolUnderReview = $conn->query("
    SELECT user_id, username, created_at
    FROM users
    WHERE verified_kol = 'under_review'
    ORDER BY created_at DESC
    LIMIT 10
");
if (!$kolUnderReview) {
    error_log("SQL Error (kol under review): " . $conn->error);
    $kolUnderReview = false;
}

closeDBConnection($conn);
?>

<div id="overview" class="page <?php echo ($currentPage == 'overview') ? 'active' : ''; ?>">

    <!-- === THỐNG KÊ NHANH === -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon" style="background: #4361ee;">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['users']); ?></h3>
                <p>Tổng Users</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #4cc9f0;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['payments_success']); ?></h3>
                <p>Payments Thành Công</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #f72585;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['withdrawals_pending']); ?></h3>
                <p>Rút Tiền Đang Chờ</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: #7209b7;">
                <i class="fas fa-tag"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($stats['plans_on_sale']); ?></h3>
                <p>Gói Đang Sale</p>
            </div>
        </div>
    </div>

    <!-- === 4 BẢNG RIÊNG BIỆT === -->
    <div class="overview-grid">

        <!-- 1. Payments Thành Công -->
        <div class="card">
            <div class="card-header">
                <h3>Thanh Toán Gần Đây</h3>
                <span class="badge success">Success</span>
            </div>
            <div class="card-body">
                <?php if ($recentPayments && $recentPayments->num_rows > 0): ?>
                    <table class="activity-table">
                        <tbody>
                            <?php while ($p = $recentPayments->fetch_assoc()): ?>
                                <tr>
                                    <td class="time"><?php echo date('d/m H:i', strtotime($p['created_at'])); ?></td>
                                    <td class="user">@<?php echo htmlspecialchars($p['username'] ?? 'N/A'); ?></td>
                                    <td class="amount text-success">
                                        +<?php echo number_format($p['amount'], 2); ?> <?php echo strtoupper($p['currency']); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty-state">Chưa có thanh toán nào</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Rút Tiền Đang Chờ -->
        <div class="card">
            <div class="card-header">
                <h3>Rút Tiền Đang Chờ Duyệt</h3>
                <span class="badge warning">Pending</span>
            </div>
            <div class="card-body">
                <?php if ($pendingWithdrawals && $pendingWithdrawals->num_rows > 0): ?>
                    <table class="activity-table">
                        <tbody>
                            <?php while ($w = $pendingWithdrawals->fetch_assoc()): ?>
                                <tr>
                                    <td class="time"><?php echo date('d/m H:i', strtotime($w['created_at'])); ?></td>
                                    <td class="user">@<?php echo htmlspecialchars($w['username'] ?? 'N/A'); ?></td>
                                    <td class="amount text-warning">
                                        -<?php echo number_format($w['amount'], 2); ?> USD
                                        <a href="?page=affiliate-withdrawals" class="small-link" title="Xem chi tiết">
                                            #<?php echo $w['withdraw_id']; ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty-state">Không có yêu cầu nào</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3. Gói Đang Sale -->
        <div class="card">
            <div class="card-header">
                <h3>Gói Đang Giảm Giá</h3>
                <span class="badge sale">Sale</span>
            </div>
            <div class="card-body">
                <?php if ($plansOnSale && $plansOnSale->num_rows > 0): ?>
                    <div class="sale-packages">
                        <?php while ($plan = $plansOnSale->fetch_assoc()): ?>
                            <div class="sale-item">
                                <div class="package-name">
                                    <strong><?php echo htmlspecialchars($plan['name']); ?></strong>
                                </div>
                                <div class="prices">
                                    <span class="original">₫<?php echo number_format($plan['price']); ?></span>
                                    <span class="sale-price">
                                        ₫<?php echo number_format($plan['sale_price'], 0); ?>
                                    </span>
                                    <span class="discount">
                                        -<?php echo (int)$plan['sale_percent']; ?>%
                                    </span>
                                </div>
                                <div class="end-date">
                                    <small>
                                        Từ: <?php echo date('d/m', strtotime($plan['sale_start'])); ?> 
                                        → Hết: <?php echo date('d/m/Y', strtotime($plan['sale_end'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="empty-state">Không có gói nào đang sale</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 4. USER KOL ĐANG CHỜ DUYỆT -->
        <div class="card">
            <div class="card-header">
                <h3>KOL Đang Chờ Duyệt</h3>
                <span class="badge info">Under Review</span>
            </div>
            <div class="card-body">
                <?php if ($kolUnderReview && $kolUnderReview->num_rows > 0): ?>
                    <table class="activity-table">
                        <tbody>
                            <?php while ($u = $kolUnderReview->fetch_assoc()): ?>
                                <tr>
                                    <td class="time">
                                        <?php echo date('d/m', strtotime($u['created_at'])); ?>
                                    </td>
                                    <td class="user">
                                        <strong>@<?php echo htmlspecialchars($u['username']); ?></strong>
                                        <small class="text-muted d-block"><?php echo htmlspecialchars($u['user_id']); ?></small>
                                    </td>
                                    <td class="amount">
                                        <a href="?page=users&action=view&id=<?php echo $u['user_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                           Xem hồ sơ
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="empty-state">Không có KOL nào đang chờ duyệt</p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- === CSS ĐẸP & RESPONSIVE === -->
<style>
.stats-cards { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
    gap: 20px; 
    margin-bottom: 30px; 
}
.stat-card { 
    background: white; 
    border-radius: 12px; 
    padding: 20px; 
    box-shadow: 0 4px 12px rgba(0,0,0,0.05); 
    display: flex; 
    align-items: center; 
    transition: all 0.2s; 
}
.stat-card:hover { transform: translateY(-3px); }
.stat-icon { 
    width: 56px; height: 56px; border-radius: 12px; 
    display: flex; align-items: center; justify-content: center; 
    color: white; font-size: 24px; margin-right: 16px; 
}
.stat-info h3 { margin: 0; font-size: 28px; font-weight: 600; }
.stat-info p { margin: 4px 0 0; color: #666; font-size: 14px; }

.overview-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); 
    gap: 20px; 
}
.card-header { 
    display: flex; justify-content: space-between; align-items: center; 
    padding-bottom: 10px; border-bottom: 1px solid #eee; 
}
.card-header h3 { margin: 0; font-size: 18px; }
.badge { 
    padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; 
    text-transform: uppercase; 
}
.badge.success { background: #d4edda; color: #155724; }
.badge.warning { background: #fff3cd; color: #856404; }
.badge.sale { background: #f8d7da; color: #721c24; }
.badge.info { background: #d1ecf1; color: #0c5460; }

.activity-table { width: 100%; border-collapse: collapse; }
.activity-table td { 
    padding: 10px 6px; border-bottom: 1px dashed #eee; font-size: 14px; 
}
.activity-table tr:last-child td { border-bottom: none; }
.activity-table .time { width: 70px; color: #555; font-weight: 500; }
.activity-table .user { color: #1a1a1a; }
.activity-table .amount { font-weight: 600; text-align: right; }
.text-success { color: #28a745; }
.text-warning { color: #ffc107; }

.sale-packages { display: flex; flex-direction: column; gap: 12px; }
.sale-item { 
    background: #f8f9fa; padding: 12px; border-radius: 8px; 
    border-left: 4px solid #f72585; 
}
.package-name { font-size: 15px; margin-bottom: 6px; }
.prices { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.original { text-decoration: line-through; color: #999; font-size: 13px; }
.sale-price { font-weight: 600; color: #d62828; font-size: 16px; }
.discount { background: #d62828; color: white; padding: 2px 6px; border-radius: 4px; font-size: 11px; }
.end-date { margin-top: 4px; font-size: 12px; color: #666; }

.small-link { color: #007bff; text-decoration: none; font-size: 11px; margin-left: 6px; }
.small-link:hover { text-decoration: underline; }

.btn-sm { padding: 4px 8px; font-size: 12px; }
.btn-outline-primary { 
    color: #4361ee; border: 1px solid #4361ee; 
}
.btn-outline-primary:hover { 
    background: #4361ee; color: white; 
}

.empty-state { text-align: center; color: #999; padding: 20px; font-style: italic; }
</style>