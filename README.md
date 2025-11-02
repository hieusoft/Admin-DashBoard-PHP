# Admin Dashboard - PHP/MySQL Project

Dự án Admin Dashboard được xây dựng bằng PHP và MySQL để quản lý hệ thống subscription.

## Cấu trúc dự án

```
block/
├── config/
│   └── db.php              # Cấu hình kết nối database
├── includes/
│   ├── header.php          # Header HTML
│   ├── sidebar.php         # Sidebar navigation
│   └── footer.php          # Footer HTML
├── pages/
│   ├── overview.php        # Trang tổng quan
│   ├── users.php           # Quản lý Users
│   ├── payments.php        # Quản lý Payments
│   ├── subscriptions.php   # Quản lý Subscriptions
│   ├── subscription-plans.php  # Quản lý Plans
│   ├── affiliate-referrals.php # Affiliate Referrals
│   ├── affiliate-withdrawals.php # Affiliate Withdrawals
│   ├── qna-categories.php  # QnA Categories
│   ├── qna.php             # Quản lý QnA
│   ├── system-logs.php     # System Logs
│   └── subscription-details.php # Subscription Details
├── api/
│   ├── add_user.php        # API thêm user
│   ├── get_user.php        # API lấy thông tin user
│   ├── delete_user.php     # API xóa user
│   ├── delete_payment.php  # API xóa payment
│   ├── delete_subscription.php # API xóa subscription
│   ├── delete_plan.php     # API xóa plan
│   ├── update_referral.php # API cập nhật referral
│   ├── update_withdrawal.php # API cập nhật withdrawal
│   ├── delete_category.php # API xóa category
│   └── delete_qna.php      # API xóa QnA
├── assets/
│   ├── css/
│   │   └── style.css       # Stylesheet chính
│   └── js/
│       └── main.js        # JavaScript chính
├── index.php              # Trang chính với routing
└── README.md              # File hướng dẫn
```

## Cài đặt

### 1. Tạo Database

Chạy file SQL để tạo database và các bảng:
- Tạo database với tên `block_db` (hoặc tên khác tùy bạn)
- Import các bảng từ SQL schema đã cung cấp

### 2. Cấu hình Database

Mở file `config/db.php` và cập nhật thông tin kết nối:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Thay đổi nếu cần
define('DB_PASS', '');            // Thay đổi nếu cần
define('DB_NAME', 'block_db');    // Thay đổi nếu cần
```

### 3. Chạy ứng dụng

- Đặt project vào thư mục web server (Apache/Nginx)
- Truy cập: `http://localhost/block/index.php`

## Tính năng

- ✅ Dashboard tổng quan với thống kê
- ✅ Quản lý Users (CRUD)
- ✅ Quản lý Payments
- ✅ Quản lý Subscriptions
- ✅ Quản lý Subscription Plans
- ✅ Quản lý Affiliate Referrals
- ✅ Quản lý Affiliate Withdrawals
- ✅ Quản lý QnA Categories
- ✅ Quản lý QnA
- ✅ Xem System Logs
- ✅ Xem Subscription Details
- ✅ Responsive design
- ✅ Pagination cho các danh sách

## Yêu cầu hệ thống

- PHP >= 7.4
- MySQL >= 5.7
- Apache/Nginx web server
- Font Awesome 6.4.0 (CDN)

## Cấu trúc Database

Dự án sử dụng các bảng chính:
- `users` - Người dùng
- `subscription_plans` - Các gói subscription
- `payments` - Thanh toán
- `subscriptions` - Subscriptions
- `subscription_details` - Chi tiết subscription
- `affiliate_referrals` - Giới thiệu affiliate
- `affiliate_withdrawals` - Rút tiền affiliate
- `qna_category` - Danh mục QnA
- `qna` - Câu hỏi và trả lời
- `system_logs` - Log hệ thống

## Lưu ý

- File `index.html` cũ vẫn còn trong project, bạn có thể xóa nếu không cần
- Cần đảm bảo quyền truy cập database đúng
- Một số tính năng CRUD đầy đủ chưa được implement (như edit), bạn có thể mở rộng thêm

## Phát triển thêm

Để mở rộng dự án:
1. Thêm các API endpoints trong thư mục `api/`
2. Tạo các form modal cho edit operations
3. Thêm validation và security measures
4. Thêm authentication/authorization
5. Thêm search và filter functionality

