<?php
$page_title = 'User Management';
require_once '../../includes/header.php';
require_once '../../includes/logger.php';

requireRole('admin');

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_user':
                $username = cleanInput($_POST['username']);
                $password = $_POST['password'];
                $nama = cleanInput($_POST['nama']);
                $role = cleanInput($_POST['role']);
                
                $errors = [];
                
                // Validation
                if (empty($username)) {
                    $errors[] = 'Username harus diisi!';
                } elseif (strlen($username) < 3) {
                    $errors[] = 'Username minimal 3 karakter!';
                }
                
                if (empty($password)) {
                    $errors[] = 'Password harus diisi!';
                } elseif (strlen($password) < 6) {
                    $errors[] = 'Password minimal 6 karakter!';
                }
                
                if (empty($nama)) {
                    $errors[] = 'Nama harus diisi!';
                }
                
                if (!in_array($role, ['admin', 'kepala_sekolah'])) {
                    $errors[] = 'Role tidak valid!';
                }
                
                // Check username uniqueness
                if (empty($errors)) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetchColumn() > 0) {
                        $errors[] = 'Username sudah digunakan!';
                    }
                }
                
                if (empty($errors)) {
                    try {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (username, password, nama, role) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$username, $hashedPassword, $nama, $role]);
                        
                        setAlert('success', 'User berhasil ditambahkan!');
                        logger()->info('New user created', ['username' => $username, 'role' => $role]);
                    } catch (PDOException $e) {
                        setAlert('danger', 'Gagal menambahkan user!');
                        logger()->error('Failed to create user', ['error' => $e->getMessage()]);
                    }
                } else {
                    foreach ($errors as $error) {
                        setAlert('danger', $error);
                    }
                }
                break;
                
            case 'edit_user':
                $id = (int)$_POST['user_id'];
                $username = cleanInput($_POST['username']);
                $nama = cleanInput($_POST['nama']);
                $role = cleanInput($_POST['role']);
                $new_password = $_POST['new_password'];
                
                $errors = [];
                
                // Validation
                if (empty($username)) {
                    $errors[] = 'Username harus diisi!';
                }
                
                if (empty($nama)) {
                    $errors[] = 'Nama harus diisi!';
                }
                
                if (!in_array($role, ['admin', 'kepala_sekolah'])) {
                    $errors[] = 'Role tidak valid!';
                }
                
                // Check username uniqueness (excluding current user)
                if (empty($errors)) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
                    $stmt->execute([$username, $id]);
                    if ($stmt->fetchColumn() > 0) {
                        $errors[] = 'Username sudah digunakan!';
                    }
                }
                
                if (empty($errors)) {
                    try {
                        if (!empty($new_password)) {
                            // Update with new password
                            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, nama = ?, role = ? WHERE id = ?");
                            $stmt->execute([$username, $hashedPassword, $nama, $role, $id]);
                        } else {
                            // Update without changing password
                            $stmt = $pdo->prepare("UPDATE users SET username = ?, nama = ?, role = ? WHERE id = ?");
                            $stmt->execute([$username, $nama, $role, $id]);
                        }
                        
                        setAlert('success', 'User berhasil diperbarui!');
                        logger()->info('User updated', ['user_id' => $id, 'username' => $username]);
                    } catch (PDOException $e) {
                        setAlert('danger', 'Gagal memperbarui user!');
                        logger()->error('Failed to update user', ['error' => $e->getMessage()]);
                    }
                } else {
                    foreach ($errors as $error) {
                        setAlert('danger', $error);
                    }
                }
                break;
                
            case 'delete_user':
                $id = (int)$_POST['user_id'];
                
                // Prevent deleting current user
                if ($id === $_SESSION['user_id']) {
                    setAlert('warning', 'Tidak dapat menghapus akun yang sedang digunakan!');
                } else {
                    try {
                        // Get user info before deletion
                        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                        $stmt->execute([$id]);
                        $user = $stmt->fetch();
                        
                        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                        $stmt->execute([$id]);
                        
                        setAlert('success', 'User berhasil dihapus!');
                        logger()->info('User deleted', ['user_id' => $id, 'username' => $user['username']]);
                    } catch (PDOException $e) {
                        setAlert('danger', 'Gagal menghapus user!');
                        logger()->error('Failed to delete user', ['error' => $e->getMessage()]);
                    }
                }
                break;
        }
        
        header('Location: index.php');
        exit();
    }
}

// Get all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

// Breadcrumb
$breadcrumb = generateBreadcrumb([
    ['text' => 'Dashboard', 'url' => '../dashboard/index.php'],
    ['text' => 'User Management', 'url' => '#']
]);
echo $breadcrumb;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-people"></i>
        User Management
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus"></i>
                Add User
            </button>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">
            <i class="bi bi-table"></i>
            System Users
            <span class="badge bg-secondary"><?php echo count($users); ?> users</span>
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                            <?php if ($user['id'] === $_SESSION['user_id']): ?>
                            <span class="badge bg-primary badge-sm">Current</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($user['nama']); ?></td>
                        <td>
                            <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger' : 'bg-info'; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($user['updated_at'])); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal"
                                    data-bs-target="#editUserModal" data-id="<?php echo $user['id']; ?>"
                                    data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                    data-nama="<?php echo htmlspecialchars($user['nama']); ?>"
                                    data-role="<?php echo $user['role']; ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>

                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn btn-outline-danger"
                                        onclick="return confirm('Are you sure you want to delete this user?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-plus"></i>
                    Add New User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="add_username" name="username" required>
                    </div>

                    <div class="mb-3">
                        <label for="add_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="add_password" name="password" required>
                        <div class="form-text">Minimum 6 characters</div>
                    </div>

                    <div class="mb-3">
                        <label for="add_nama" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="add_nama" name="nama" required>
                    </div>

                    <div class="mb-3">
                        <label for="add_role" class="form-label">Role</label>
                        <select class="form-select" id="add_role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin">Administrator</option>
                            <option value="kepala_sekolah">Kepala Sekolah</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Add User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil"></i>
                    Edit User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="edit_new_password" name="new_password">
                        <div class="form-text">Leave empty to keep current password</div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_nama" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="edit_nama" name="nama" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_role" class="form-label">Role</label>
                        <select class="form-select" id="edit_role" name="role" required>
                            <option value="admin">Administrator</option>
                            <option value="kepala_sekolah">Kepala Sekolah</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-save"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Handle edit user modal
document.getElementById('editUserModal').addEventListener('show.bs.modal', function(event) {
    const button = event.relatedTarget;
    const id = button.getAttribute('data-id');
    const username = button.getAttribute('data-username');
    const nama = button.getAttribute('data-nama');
    const role = button.getAttribute('data-role');

    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_role').value = role;
    document.getElementById('edit_new_password').value = '';
});
</script>

<?php require_once '../../includes/footer.php'; ?>