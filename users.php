<?php
$pageTitle = 'Manajemen Pengguna';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save') {
            $id = (int) ($_POST['id'] ?? 0);
            $nama = trim($_POST['nama'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'petugas';
            $status = $_POST['status'] ?? 'aktif';

            if ($nama === '' || $username === '') {
                throw new RuntimeException('Nama dan username wajib diisi.');
            }
            if (!in_array($role, ['admin', 'petugas', 'kepala_lab'], true)) {
                throw new RuntimeException('Role tidak valid.');
            }
            if (!in_array($status, ['aktif', 'nonaktif'], true)) {
                throw new RuntimeException('Status tidak valid.');
            }

            if ($id > 0) {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = db()->prepare('UPDATE users SET nama=?, username=?, password=?, role=?, status=? WHERE id=?');
                    $stmt->execute([$nama, $username, $hash, $role, $status, $id]);
                } else {
                    $stmt = db()->prepare('UPDATE users SET nama=?, username=?, role=?, status=? WHERE id=?');
                    $stmt->execute([$nama, $username, $role, $status, $id]);
                }
                log_audit('update', 'users', $id, $username);
                set_flash('success', 'Pengguna berhasil diperbarui.');
            } else {
                if ($password === '') {
                    throw new RuntimeException('Password wajib diisi untuk pengguna baru.');
                }
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = db()->prepare('INSERT INTO users (nama, username, password, role, status) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$nama, $username, $hash, $role, $status]);
                log_audit('create', 'users', (int) db()->lastInsertId(), $username);
                set_flash('success', 'Pengguna berhasil ditambahkan.');
            }
        } elseif ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id === (int) current_user()['id']) {
                throw new RuntimeException('Akun yang sedang digunakan tidak dapat dihapus.');
            }
            $stmt = db()->prepare('DELETE FROM users WHERE id=?');
            $stmt->execute([$id]);
            log_audit('delete', 'users', $id);
            set_flash('success', 'Pengguna berhasil dihapus.');
        }
    } catch (Throwable $e) {
        set_flash('danger', 'Gagal memproses data: ' . $e->getMessage());
    }
    redirect('users.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM users WHERE id=?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit = $stmt->fetch();
}
$rows = db()->query('SELECT * FROM users ORDER BY nama')->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0"><?= $edit ? 'Edit Pengguna' : 'Tambah Pengguna' ?></h2></div>
            <div class="card-body px-4 pb-4">
                <form method="post" autocomplete="off">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
                    <div class="mb-3"><label class="required">Nama</label><input type="text" name="nama" class="form-control" required value="<?= e($edit['nama'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="required">Username</label><input type="text" name="username" class="form-control" required value="<?= e($edit['username'] ?? '') ?>"></div>
                    <div class="mb-3"><label class="<?= $edit ? '' : 'required' ?>">Password <?= $edit ? '<small class="text-muted">(kosongkan jika tidak diubah)</small>' : '' ?></label><input type="password" name="password" class="form-control" <?= $edit ? '' : 'required' ?>></div>
                    <div class="mb-3"><label class="required">Role</label>
                        <select name="role" class="form-control" required>
                            <option value="admin" <?= selected($edit['role'] ?? '', 'admin') ?>>Admin</option>
                            <option value="petugas" <?= selected($edit['role'] ?? 'petugas', 'petugas') ?>>Petugas Lab</option>
                            <option value="kepala_lab" <?= selected($edit['role'] ?? '', 'kepala_lab') ?>>Kepala Lab</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="required">Status</label>
                        <select name="status" class="form-control" required>
                            <option value="aktif" <?= selected($edit['status'] ?? 'aktif', 'aktif') ?>>Aktif</option>
                            <option value="nonaktif" <?= selected($edit['status'] ?? '', 'nonaktif') ?>>Nonaktif</option>
                        </select>
                    </div>
                    <button class="btn btn-primary"><i class="bi bi-save mr-1"></i>Simpan</button>
                    <?php if ($edit): ?><a href="users.php" class="btn btn-light border">Batal</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0">Daftar Pengguna</h2></div>
            <div class="card-body px-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Nama</th><th>Username</th><th>Role</th><th>Status</th><th>Login Terakhir</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= e($row['nama']) ?></td>
                                <td><?= e($row['username']) ?></td>
                                <td><?= status_badge($row['role']) ?></td>
                                <td><?= status_badge($row['status']) ?></td>
                                <td><?= e(tanggal_jam($row['last_login'])) ?></td>
                                <td class="text-end">
                                    <a href="users.php?edit=<?= e($row['id']) ?>" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a>
                                    <?php if ((int) $row['id'] !== (int) current_user()['id']): ?>
                                    <form method="post" class="d-inline" onsubmit="return confirmDelete('Hapus pengguna ini?')">
                                        <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($row['id']) ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
