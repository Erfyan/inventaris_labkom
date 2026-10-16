<?php
$pageTitle = 'Supplier';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_login();

$canManage = can_manage_data();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    if (!$canManage) {
        set_flash('danger', 'Anda tidak memiliki hak untuk mengubah data.');
        redirect('supplier.php');
    }

    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save') {
            $id = (int) ($_POST['id'] ?? 0);
            $nama = trim($_POST['nama'] ?? '');
            $kontak = trim($_POST['kontak'] ?? '');
            $telepon = trim($_POST['telepon'] ?? '');
            $alamat = trim($_POST['alamat'] ?? '');

            if ($nama === '') {
                throw new RuntimeException('Nama supplier wajib diisi.');
            }

            if ($id > 0) {
                $stmt = db()->prepare('UPDATE supplier SET nama=?, kontak=?, telepon=?, alamat=? WHERE id=?');
                $stmt->execute([$nama, $kontak, $telepon, $alamat, $id]);
                log_audit('update', 'supplier', $id, $nama);
                set_flash('success', 'Supplier berhasil diperbarui.');
            } else {
                $stmt = db()->prepare('INSERT INTO supplier (nama, kontak, telepon, alamat) VALUES (?, ?, ?, ?)');
                $stmt->execute([$nama, $kontak, $telepon, $alamat]);
                log_audit('create', 'supplier', (int) db()->lastInsertId(), $nama);
                set_flash('success', 'Supplier berhasil ditambahkan.');
            }
        } elseif ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = db()->prepare('DELETE FROM supplier WHERE id=?');
            $stmt->execute([$id]);
            log_audit('delete', 'supplier', $id);
            set_flash('success', 'Supplier berhasil dihapus.');
        }
    } catch (Throwable $e) {
        set_flash('danger', 'Gagal memproses data: ' . $e->getMessage());
    }
    redirect('supplier.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM supplier WHERE id=?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit = $stmt->fetch();
}

$rows = db()->query('SELECT s.*, COUNT(b.id) AS total_barang FROM supplier s LEFT JOIN barang b ON b.supplier_id=s.id GROUP BY s.id ORDER BY s.nama')->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-4 <?php if (!$canManage) echo 'd-none'; ?>">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0"><?= $edit ? 'Edit Supplier' : 'Tambah Supplier' ?></h2></div>
            <div class="card-body px-4 pb-4">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
                    <div class="mb-3"><label class="required">Nama Supplier</label><input type="text" name="nama" class="form-control" required value="<?= e($edit['nama'] ?? '') ?>"></div>
                    <div class="mb-3"><label>Kontak Person</label><input type="text" name="kontak" class="form-control" value="<?= e($edit['kontak'] ?? '') ?>"></div>
                    <div class="mb-3"><label>Telepon</label><input type="text" name="telepon" class="form-control" value="<?= e($edit['telepon'] ?? '') ?>"></div>
                    <div class="mb-3"><label>Alamat</label><textarea name="alamat" class="form-control" rows="3"><?= e($edit['alamat'] ?? '') ?></textarea></div>
                    <button class="btn btn-primary"><i class="bi bi-save mr-1"></i>Simpan</button>
                    <?php if ($edit): ?><a href="supplier.php" class="btn btn-light border">Batal</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="<?= $canManage ? 'col-lg-8' : 'col-12' ?>">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0">Daftar Supplier</h2></div>
            <div class="card-body px-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Nama</th><th>Kontak</th><th>Telepon</th><th>Alamat</th><th>Total Barang</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= e($row['nama']) ?></td><td><?= e($row['kontak']) ?></td><td><?= e($row['telepon']) ?></td><td><?= e($row['alamat']) ?></td><td><?= e($row['total_barang']) ?></td>
                                <td class="text-end">
                                    <?php if ($canManage): ?>
                                        <a href="supplier.php?edit=<?= e($row['id']) ?>" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a>
                                        <form method="post" class="d-inline" onsubmit="return confirmDelete('Hapus supplier ini?')">
                                            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($row['id']) ?>">
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    <?php else: ?><span class="text-muted">-</span><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted">Belum ada data.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
