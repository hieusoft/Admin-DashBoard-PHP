-- ============================================
-- DATABASE INDEXES OPTIMIZATION
-- Chạy file này để tăng tốc độ queries
-- ============================================

-- Indexes cho bảng users
CREATE INDEX IF NOT EXISTS idx_users_created_at ON users(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_users_ref_by ON users(ref_by);
CREATE INDEX IF NOT EXISTS idx_users_verified_kol ON users(verified_kol);
CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_users_user_id_username ON users(user_id, username);

-- Indexes cho bảng payments
CREATE INDEX IF NOT EXISTS idx_payments_status ON payments(status);
CREATE INDEX IF NOT EXISTS idx_payments_created_at ON payments(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_payments_user_id ON payments(user_id);
CREATE INDEX IF NOT EXISTS idx_payments_plan_id ON payments(plan_id);
CREATE INDEX IF NOT EXISTS idx_payments_status_created_at ON payments(status, created_at DESC);

-- Indexes cho bảng subscriptions
CREATE INDEX IF NOT EXISTS idx_subscriptions_created_at ON subscriptions(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_subscriptions_user_id ON subscriptions(user_id);
CREATE INDEX IF NOT EXISTS idx_subscriptions_status ON subscriptions(status);
CREATE INDEX IF NOT EXISTS idx_subscriptions_user_status ON subscriptions(user_id, status);

-- Indexes cho bảng subscription_plans
CREATE INDEX IF NOT EXISTS idx_plans_is_active ON subscription_plans(is_active);
CREATE INDEX IF NOT EXISTS idx_plans_sale_dates ON subscription_plans(sale_start, sale_end);

-- Indexes cho bảng affiliate_withdrawals
CREATE INDEX IF NOT EXISTS idx_withdrawals_status ON affiliate_withdrawals(status);
CREATE INDEX IF NOT EXISTS idx_withdrawals_created_at ON affiliate_withdrawals(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_withdrawals_user_id ON affiliate_withdrawals(user_id);
CREATE INDEX IF NOT EXISTS idx_withdrawals_status_created_at ON affiliate_withdrawals(status, created_at DESC);

-- Indexes cho bảng qna
CREATE INDEX IF NOT EXISTS idx_qna_category_id ON qna(category_id);
CREATE INDEX IF NOT EXISTS idx_qna_created_at ON qna(created_at DESC);

-- Indexes cho bảng qna_category
CREATE INDEX IF NOT EXISTS idx_qna_category_created_at ON qna_category(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_qna_category_is_active ON qna_category(is_active);

-- Indexes cho bảng system_logs (nếu có)
CREATE INDEX IF NOT EXISTS idx_logs_created_at ON system_logs(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_logs_user_id ON system_logs(user_id);

-- ============================================
-- LƯU Ý:
-- 1. Indexes sẽ chiếm thêm dung lượng database
-- 2. Tăng tốc SELECT nhưng làm chậm INSERT/UPDATE
-- 3. Với database nhỏ (<10k records), có thể không cần tất cả indexes
-- 4. Monitor performance sau khi add indexes
-- ============================================

