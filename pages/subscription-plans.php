<?php
require_once __DIR__ . '/../config/db.php';
$conn = getDBConnection();

$plans = $conn->query("SELECT * FROM subscription_plans ORDER BY plan_id ASC");

closeDBConnection($conn);
?>

<div id="subscription-plans" class="page <?php echo ($currentPage == 'subscription-plans') ? 'active' : ''; ?>">
    <div class="card">
        <div class="card-header">
            <h2>Quản lý Subscription Plans</h2>
            <button class="btn btn-primary" onclick="openModal('addPlanModal')">
                <i class="fas fa-plus"></i> Thêm Plan
            </button>
        </div>
        <div class="card-body">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Sale %</th>
                        <th>Sale Start</th>
                        <th>Sale End</th>
                        <th>Duration (days)</th>
                        <th>Active</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($plans && $plans->num_rows > 0): ?>
                        <?php while($plan = $plans->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $plan['plan_id']; ?></td>
                                <td><?php echo htmlspecialchars($plan['name']); ?></td>
                                <td><?php echo number_format($plan['price'], 2); ?> USDT</td>
                                <td><?php echo $plan['sale_percent']; ?>%</td>
                                <td><?php echo $plan['sale_start'] ? date('d/m/Y H:i', strtotime($plan['sale_start'])) : 'N/A'; ?></td>
                                <td><?php echo $plan['sale_end'] ? date('d/m/Y H:i', strtotime($plan['sale_end'])) : 'N/A'; ?></td>
                                <td><?php echo $plan['duration_days']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $plan['is_active'] ? 'status-active' : 'status-pending'; ?>">
                                        <?php echo $plan['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <div class="action-btn btn-info" 
                                             onclick="editPlan(<?php echo $plan['plan_id']; ?>)"
                                             title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </div>
                                        <div class="action-btn btn-danger"
                                             onclick="deletePlan(<?php echo $plan['plan_id']; ?>)"
                                             title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="empty-state">
                                <i class="fas fa-list-alt"></i>
                                <p>Chưa có plan nào</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Plan Modal -->
<div id="addPlanModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Thêm Subscription Plan</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addPlanForm" method="post" onsubmit="return false;">
                <div class="form-group">
                    <label>Plan Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label>Price (USDT)</label>
                            <input type="number" name="price" class="form-control" step="0.01" value="0.00" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label>Sale Percent (%)</label>
                            <input type="number" name="sale_percent" class="form-control" step="0.01" value="0.00" min="0" max="100">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label>Sale Start</label>
                            <input type="datetime-local" name="sale_start" class="form-control">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label>Sale End</label>
                            <input type="datetime-local" name="sale_end" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label>Duration (days)</label>
                            <input type="number" name="duration_days" class="form-control" min="1" value="30" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label>Active</label>
                            <select name="is_active" class="form-control">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" onclick="closeModal()">Hủy</button>
            <button type="button" onclick="document.getElementById('addPlanForm').dispatchEvent(new Event('submit'));" class="btn btn-primary">Lưu</button>
        </div>
    </div>
</div>

<!-- Edit Plan Modal -->
<div id="editPlanModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Sửa Subscription Plan</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editPlanForm" method="post" onsubmit="return false;">
                <input type="hidden" name="plan_id" id="edit_plan_id">
                <div class="form-group">
                    <label>Plan ID</label>
                    <input type="number" id="edit_display_plan_id" class="form-control disabled-input" disabled>
                </div>
                <div class="form-group">
                    <label>Plan Name</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label>Price (USDT)</label>
                            <input type="number" name="price" id="edit_price" class="form-control" step="0.01" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label>Sale Percent (%)</label>
                            <input type="number" name="sale_percent" id="edit_sale_percent" class="form-control" step="0.01" min="0" max="100">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label>Sale Start</label>
                            <input type="datetime-local" name="sale_start" id="edit_sale_start" class="form-control">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label>Sale End</label>
                            <input type="datetime-local" name="sale_end" id="edit_sale_end" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label>Duration (days)</label>
                            <input type="number" name="duration_days" id="edit_duration_days" class="form-control" min="1" required>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label>Active</label>
                            <select name="is_active" id="edit_is_active" class="form-control">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" onclick="closeModal()">Hủy</button>
            <button type="button" onclick="document.getElementById('editPlanForm').dispatchEvent(new Event('submit'));" class="btn btn-primary">Cập nhật</button>
        </div>
    </div>
</div>

<script>
function editPlan(planId) {
    // Load plan data
    fetch('api/get_plan.php?id=' + planId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.plan) {
                const plan = data.plan;
                
                // Populate form fields
                document.getElementById('edit_plan_id').value = plan.plan_id;
                document.getElementById('edit_display_plan_id').value = plan.plan_id;
                document.getElementById('edit_name').value = plan.name || '';
                document.getElementById('edit_price').value = plan.price || 0.00;
                document.getElementById('edit_sale_percent').value = plan.sale_percent || 0.00;
                
                // Format datetime-local input (convert from MySQL datetime format)
                if (plan.sale_start) {
                    const saleStart = new Date(plan.sale_start);
                    document.getElementById('edit_sale_start').value = saleStart.toISOString().slice(0, 16);
                } else {
                    document.getElementById('edit_sale_start').value = '';
                }
                
                if (plan.sale_end) {
                    const saleEnd = new Date(plan.sale_end);
                    document.getElementById('edit_sale_end').value = saleEnd.toISOString().slice(0, 16);
                } else {
                    document.getElementById('edit_sale_end').value = '';
                }
                
                document.getElementById('edit_duration_days').value = plan.duration_days || 30;
                document.getElementById('edit_is_active').value = plan.is_active || 1;
                
                // Open modal
                openModal('editPlanModal');
            } else {
                alert('Không thể tải thông tin plan: ' + (data.message || 'Lỗi không xác định'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi tải thông tin plan');
        });
}

function deletePlan(planId) {
    if (confirm('Bạn có chắc chắn muốn xóa plan này?')) {
        fetch('api/delete_plan.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({plan_id: planId})
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
            alert('Có lỗi xảy ra khi xóa plan');
        });
    }
}

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Handle Add Plan Form
    const addForm = document.getElementById('addPlanForm');
    if (addForm) {
        addForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const formData = new FormData(addForm);
            const data = Object.fromEntries(formData);
            
            // Convert empty datetime strings to null
            if (data.sale_start === '') data.sale_start = null;
            if (data.sale_end === '') data.sale_end = null;
            
            // Convert to integers
            data.duration_days = parseInt(data.duration_days);
            data.is_active = parseInt(data.is_active);
            
            try {
                const response = await fetch('api/add_plan.php', {
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
    
    // Handle Edit Plan Form
    const editForm = document.getElementById('editPlanForm');
    if (editForm) {
        editForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const formData = new FormData(editForm);
            const data = Object.fromEntries(formData);
            
            // Convert empty datetime strings to null
            if (data.sale_start === '') data.sale_start = null;
            if (data.sale_end === '') data.sale_end = null;
            
            // Convert to integers
            data.plan_id = parseInt(data.plan_id);
            data.duration_days = parseInt(data.duration_days);
            data.is_active = parseInt(data.is_active);
            
            try {
                const response = await fetch('api/update_plan.php', {
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

