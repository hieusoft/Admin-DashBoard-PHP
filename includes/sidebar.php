<div class="sidebar">
    <div class="sidebar-header">
        <h2>Admin Dashboard</h2>
        <p>Quản lý hệ thống</p>
    </div>
    <div class="sidebar-menu">
        <a href="index.php?page=overview" class="menu-item <?php echo ($currentPage == 'overview') ? 'active' : ''; ?>" data-page="overview">
            <i class="fas fa-tachometer-alt"></i>
            <span>Overview</span>
        </a>
        <a href="index.php?page=users" class="menu-item <?php echo ($currentPage == 'users') ? 'active' : ''; ?>" data-page="users">
            <i class="fas fa-users"></i>
            <span>Users</span>
        </a>
        <a href="index.php?page=payments" class="menu-item <?php echo ($currentPage == 'payments') ? 'active' : ''; ?>" data-page="payments">
            <i class="fas fa-credit-card"></i>
            <span>Payments</span>
        </a>
        <a href="index.php?page=subscriptions" class="menu-item <?php echo ($currentPage == 'subscriptions') ? 'active' : ''; ?>" data-page="subscriptions">
            <i class="fas fa-calendar-check"></i>
            <span>Subscriptions</span>
        </a>
        <a href="index.php?page=subscription-plans" class="menu-item <?php echo ($currentPage == 'subscription-plans') ? 'active' : ''; ?>" data-page="subscription-plans">
            <i class="fas fa-list-alt"></i>
            <span>Subscription Plans</span>
        </a>
        <a href="index.php?page=affiliate-withdrawals" class="menu-item <?php echo ($currentPage == 'affiliate-withdrawals') ? 'active' : ''; ?>" data-page="affiliate-withdrawals">
            <i class="fas fa-money-bill-wave"></i>
            <span>Affiliate Withdrawals</span>
        </a>
        <a href="index.php?page=qna-categories" class="menu-item <?php echo ($currentPage == 'qna-categories') ? 'active' : ''; ?>" data-page="qna-categories">
            <i class="fas fa-folder"></i>
            <span>QnA Categories</span>
        </a>
        <a href="index.php?page=qna" class="menu-item <?php echo ($currentPage == 'qna') ? 'active' : ''; ?>" data-page="qna">
            <i class="fas fa-question-circle"></i>
            <span>QnA</span>
        </a>
        <a href="index.php?page=system-logs" class="menu-item <?php echo ($currentPage == 'system-logs') ? 'active' : ''; ?>" data-page="system-logs">
            <i class="fas fa-clipboard-list"></i>
            <span>System Logs</span>
        </a>
    </div>
</div>

