<?php
require_once __DIR__ . '/../config/db.php';
$conn = getDBConnection();

// Lấy danh sách categories
$categories = $conn->query("SELECT * FROM qna_category ORDER BY created_at DESC");

closeDBConnection($conn);
?>

<div id="qna-categories" class="page <?php echo ($currentPage == 'qna-categories') ? 'active' : ''; ?>">
    <div class="card">
        <div class="card-header">
            <h2>Quản lý QnA Categories</h2>
            <button class="btn btn-primary" onclick="openAddModal()">
                Thêm Category
            </button>
        </div>
        <div class="card-body">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên Category</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($categories && $categories->num_rows > 0): ?>
                        <?php while($cat = $categories->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $cat['category_id']; ?></td>
                                <td><?php echo htmlspecialchars($cat['category_name']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $cat['is_active'] ? 'status-active' : 'status-pending'; ?>">
                                        <?php echo $cat['is_active'] ? 'Hoạt động' : 'Tạm dừng'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($cat['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <!-- VIEW QnA -->
                                        <div class="action-btn btn-primary" 
                                             onclick="openViewModal(<?php echo $cat['category_id']; ?>, '<?php echo addslashes($cat['category_name']); ?>')"
                                             title="Xem QnA">
                                            <i class="fas fa-eye"></i>
                                        </div>
                                        <!-- EDIT -->
                                        <div class="action-btn btn-info" 
                                             onclick="openEditModal(<?php echo $cat['category_id']; ?>, '<?php echo addslashes($cat['category_name']); ?>', <?php echo $cat['is_active']; ?>)"
                                             title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </div>
                                        <!-- DELETE -->
                                        <div class="action-btn btn-danger" 
                                             onclick="deleteCategory(<?php echo $cat['category_id']; ?>)"
                                             title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty-state">
                                Chưa có category nào
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- === MODAL THÊM CATEGORY === -->
<div id="addCategoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Thêm Category Mới</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="addCategoryForm" onsubmit="return false;">
                <div class="form-group">
                    <label>Tên Category <span class="required-mark">*</span></label>
                    <input type="text" name="category_name" class="form-control" required placeholder="VD: Lỗi kết nối">
                </div>
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="is_active" class="form-control">
                        <option value="1">Hoạt động</option>
                        <option value="0">Tạm dừng</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()">Hủy</button>
            <button class="btn btn-primary" onclick="submitAddCategory()">Thêm</button>
        </div>
    </div>
</div>

<!-- === MODAL SỬA CATEGORY === -->
<div id="editCategoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Sửa Category</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="editCategoryForm" onsubmit="return false;">
                <input type="hidden" name="category_id" id="edit_category_id">
                <div class="form-group">
                    <label>Tên Category <span class="required-mark">*</span></label>
                    <input type="text" name="category_name" id="edit_category_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="is_active" id="edit_is_active" class="form-control">
                        <option value="1">Hoạt động</option>
                        <option value="0">Tạm dừng</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()">Hủy</button>
            <button class="btn btn-primary" onclick="submitEditCategory()">Lưu</button>
        </div>
    </div>
</div>

<!-- === MODAL XEM QnA TRONG CATEGORY === -->
<div id="viewQnaModal" class="modal">
    <div class="modal-content large">
        <div class="modal-header">
            <h3 id="viewModalTitle">QnA trong Category</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="current_category_id">
            <div class="qna-header">
                <button class="btn btn-success btn-sm" onclick="openAddQnaForm()">
                    Thêm QnA
                </button>
            </div>
            <div id="qnaList">
                <!-- Load bằng JS -->
            </div>
        </div>
    </div>
</div>

<!-- === MODAL THÊM QnA === -->
<div id="addQnaModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Thêm QnA Mới</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="addQnaForm" onsubmit="return false;">
                <input type="hidden" name="category_id" id="add_qna_category_id">
                <div class="form-group">
                    <label>Câu hỏi <span class="required-mark">*</span></label>
                    <input type="text" name="question" class="form-control" required placeholder="VD: Làm sao để kết nối ví?">
                </div>
                <div class="form-group">
                    <label>Câu trả lời <span class="required-mark">*</span></label>
                    <textarea name="answer" class="form-control" rows="4" required placeholder="Nhập câu trả lời..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()">Hủy</button>
            <button class="btn btn-primary" onclick="submitAddQna()">Thêm</button>
        </div>
    </div>
</div>

<!-- === JS CHỨC NĂNG === -->
<script>
let currentCategoryId = 0;

// --- CATEGORY ---
function openAddModal() {
    document.getElementById('addCategoryForm').reset();
    openModal('addCategoryModal');
}

function openEditModal(id, name, isActive) {
    document.getElementById('edit_category_id').value = id;
    document.getElementById('edit_category_name').value = name;
    document.getElementById('edit_is_active').value = isActive;
    openModal('editCategoryModal');
}

function submitAddCategory() {
    const data = getFormData('addCategoryForm');
    if (!data.category_name.trim()) return alert('Nhập tên category!');
    postData('api/add_category.php', data, () => {
        alert('Thêm thành công!');
        closeModal(); location.reload();
    });
}

function submitEditCategory() {
    const data = getFormData('editCategoryForm');
    if (!data.category_name.trim()) return alert('Nhập tên category!');
    postData('api/update_category.php', data, () => {
        alert('Cập nhật thành công!');
        closeModal(); location.reload();
    });
}

function deleteCategory(id) {
    if (confirm('Xóa category này?\nTất cả QnA sẽ bị xóa!')) {
        postData('api/delete_category.php', { category_id: id }, () => {
            alert('Xóa thành công!');
            location.reload();
        });
    }
}

// --- QnA ---
function openViewModal(catId, catName) {
    currentCategoryId = catId;
    document.getElementById('current_category_id').value = catId;
    document.getElementById('viewModalTitle').textContent = `QnA: ${catName}`;
    loadQnaList(catId);
    openModal('viewQnaModal');
}

function loadQnaList(catId) {
    fetch(`api/get_qna.php?category_id=${catId}`)
        .then(r => r.json())
        .then(d => {
            const list = document.getElementById('qnaList');
            if (d.success && d.qna.length > 0) {
                list.innerHTML = d.qna.map(q => `
                    <div class="qna-item">
                        <div class="qna-question">
                            <strong>Q:</strong> ${escapeHtml(q.question)}
                        </div>
                        <div class="qna-answer">
                            <strong>A:</strong> ${escapeHtml(q.answer)}
                        </div>
                        <div class="qna-actions">
                            <button class="btn btn-danger btn-sm" onclick="deleteQna(${q.qna_id})">
                                Delete
                            </button>
                        </div>
                    </div>
                `).join('');
            } else {
                list.innerHTML = '<p class="empty-state">Chưa có QnA nào</p>';
            }
        });
}

function openAddQnaForm() {
    document.getElementById('add_qna_category_id').value = currentCategoryId;
    document.getElementById('addQnaForm').reset();
    openModal('addQnaModal');
}

function submitAddQna() {
    const data = getFormData('addQnaForm');
    if (!data.question.trim() || !data.answer.trim()) return alert('Vui lòng nhập đầy đủ!');
    postData('api/add_qna.php', data, () => {
        alert('Thêm QnA thành công!');
        closeModal();
        loadQnaList(currentCategoryId);
    });
}

function deleteQna(qnaId) {
    if (confirm('Xóa QnA này?')) {
        postData('api/delete_qna.php', { qna_id: qnaId }, () => {
            alert('Xóa thành công!');
            loadQnaList(currentCategoryId);
        });
    }
}

// --- HỖ TRỢ ---
function getFormData(formId) {
    return Object.fromEntries(new FormData(document.getElementById(formId)));
}

function postData(url, data, onSuccess) {
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) onSuccess();
        else alert('Lỗi: ' + d.message);
    })
    .catch(() => alert('Lỗi mạng'));
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function openModal(id) {
    closeModal();
    document.getElementById(id).style.display = 'block';
}

function closeModal() {
    document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
}

window.onclick = e => { if (e.target.classList.contains('modal')) closeModal(); };
</script>

<!-- === CSS ĐẸP === -->
<style>
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); overflow-y: auto; }
.modal-content { background: white; margin: 5% auto; padding: 0; width: 90%; max-width: 800px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
.modal-header { padding: 16px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; background: #f8f9fa; border-radius: 12px 12px 0 0; }
.modal-header h3 { margin: 0; font-size: 18px; }
.modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #aaa; }
.modal-close:hover { color: #000; }
.modal-body { padding: 20px; max-height: 70vh; overflow-y: auto; }
.modal-footer { padding: 16px 20px; border-top: 1px solid #eee; text-align: right; display: flex; justify-content: flex-end; gap: 10px; }

.form-group { margin-bottom: 16px; }
.form-group label { display: block; margin-bottom: 6px; font-weight: 500; }
.form-control { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
.form-control:focus { outline: none; border-color: #4361ee; box-shadow: 0 0 0 2px rgba(67,97,238,0.2); }

.action-buttons { display: flex; gap: 6px; }
.action-btn { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; cursor: pointer; font-size: 14px; }
.action-btn:hover { opacity: 0.9; }

.status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.status-active { background: #d4edda; color: #155724; }
.status-pending { background: #fff3cd; color: #856404; }

.qna-header { margin-bottom: 16px; text-align: right; }
.qna-item { background: #f8f9fa; padding: 14px; border-radius: 8px; margin-bottom: 12px; border-left: 4px solid #4361ee; }
.qna-question { margin-bottom: 8px; font-size: 15px; }
.qna-answer { margin-bottom: 8px; color: #333; font-size: 14px; }
.qna-actions { text-align: right; }

.empty-state { text-align: center; padding: 30px; color: #999; font-style: italic; }
</style>