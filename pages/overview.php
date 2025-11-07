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
$recentPaymentsQuery = "
    SELECT p.amount, p.currency, u.username, p.created_at
    FROM payments p
    INNER JOIN users u ON p.user_id = u.user_id
    WHERE p.status = 'success'
    ORDER BY p.created_at DESC
    LIMIT 5
";
$recentPayments = $conn->query($recentPaymentsQuery);
if (!$recentPayments) {
    error_log("SQL Error (recent payments): " . $conn->error);
    $recentPayments = false;
}

// === 3. RÚT TIỀN ĐANG CHỜ ===
$pendingWithdrawalsQuery = "
    SELECT aw.withdraw_id, aw.amount, u.username, aw.created_at
    FROM affiliate_withdrawals aw
    INNER JOIN users u ON aw.user_id = u.user_id
    WHERE aw.status = 'pending'
    ORDER BY aw.created_at DESC
    LIMIT 5
";
$pendingWithdrawals = $conn->query($pendingWithdrawalsQuery);
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