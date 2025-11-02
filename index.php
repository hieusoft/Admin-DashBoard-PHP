<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Get current page from query string
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'overview';

// Valid pages
$validPages = [
    'overview',
    'users',
    'payments',
    'subscriptions',
    'subscription-plans',
    'affiliate-withdrawals',
    'qna-categories',
    'qna',
    'system-logs'
];

// Validate page
if (!in_array($currentPage, $validPages)) {
    $currentPage = 'overview';
}

// Include header
require_once 'includes/header.php';
?>

<!-- Sidebar -->
<?php require_once 'includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1>Dashboard</h1>
        </div>
        <div class="header-right">
            <div class="user-info">
                <div class="user-avatar">AD</div>
                <span>Admin User</span>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="content">
        <?php
        // Include current page
        $pageFile = 'pages/' . $currentPage . '.php';
        if (file_exists($pageFile)) {
            try {
                require_once $pageFile;
            } catch (Exception $e) {
                echo '<div class="page active"><div class="card"><div class="card-body">';
                echo '<div style="padding: 20px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;">';
                echo '<strong>Lỗi:</strong> ' . htmlspecialchars($e->getMessage());
                echo '<br><br><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
                echo '</div></div></div>';
            }
        } else {
            echo '<div class="page active"><div class="card"><div class="card-body"><p>Page not found: ' . htmlspecialchars($currentPage) . '</p></div></div></div>';
        }
        ?>
    </div>
</div>

<?php
// Include footer
require_once 'includes/footer.php';
?>

