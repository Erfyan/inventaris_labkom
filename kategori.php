<?php
$pageTitle = 'Kategori Barang';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_login();

$canManage = can_manage_data();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    if (!$canManage) {
        set_flash('danger', 'Anda tidak memiliki hak untuk mengubah data.');
        redirect('kategori.php');
    }

    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save') {
            $id = (int) ($_POST['id'] ?? 0);
            $kode = strtoupper(trim($_POST['kode'] ?? ''));
            $nama = trim($_POST['nama'] ?? '');
            $keterangan = trim($_POST['keterangan'] ?? '');

            if ($nama === '') {
                throw new RuntimeException('Nama kategori wajib diisi.');
            }

            if ($id > 0) {
                $stmt = db()->prepare('UPDATE kategori SET kode=?, nama=?, keterangan=? WHERE id=?');
                $stmt->execute([$kode, $nama, $keterangan, $id]);
                log_audit('update', 'kategori', $id, $nama);
                set_flash('success', 'Kategori berhasil diperbarui.');
            } else {
                $stmt = db()->prepare('INSERT INTO kategori (kode, nama, keterangan) VALUES (?, ?, ?)');
                $stmt->execute([$kode, $nama, $keterangan]);
                log_audit('create', 'kategori', (int) db()->lastInsertId(), $nama);
                set_flash('success', 'Kategori berhasil ditambahkan.');
            }
        } elseif ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = db()->prepare('DELETE FROM kategori WHERE id=?');
            $stmt->execute([$id]);
            log_audit('delete', 'kategori', $id);
            set_flash('success', 'Kategori berhasil dihapus.');
        }
    } catch (Throwable $e) {
        set_flash('danger', 'Gagal memproses data: ' . $e->getMessage());
    }
    redirect('kategori.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM kategori WHERE id=?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit = $stmt->fetch();
}

$rows = db()->query('SELECT k.*, COUNT(b.id) AS total_barang FROM kategori k LEFT JOIN barang b ON b.kategori_id=k.id GROUP BY k.id ORDER BY k.nama')->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-4 <?php if (!$canManage) echo 'd-none'; ?>">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0"><?= $edit ? 'Edit Kategori' : 'Tambah Kategori' ?></h2></div>
            <div class="card-body px-4 pb-4">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
                    <div class="mb-3">
                        <label>Kode</label>
                        <input type="text" name="kode" class="form-control" value="<?= e($edit['kode'] ?? '') ?>" placeholder="ELK">
                    </div>
                    <div class="mb-3">
                        <label class="required">Nama Kategori</label>
                        <input type="text" name="nama" class="form-control" required value="<?= e($edit['nama'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label>Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="3"><?= e($edit['keterangan'] ?? '') ?></textarea>
                    </div>
                    <button class="btn btn-primary"><i class="bi bi-save mr-1"></i>Simpan</button>
                    <?php if ($edit): ?><a href="kategori.php" class="btn btn-light border">Batal</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="<?= $canManage ? 'col-lg-8' : 'col-12' ?>">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0">Daftar Kategori</h2></div>
            <div class="card-body px-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Kode</th><th>Nama</th><th>Keterangan</th><th>Total Barang</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= e($row['kode']) ?></td>
                                <td><?= e($row['nama']) ?></td>
                                <td><?= e($row['keterangan']) ?></td>
                                <td><?= e($row['total_barang']) ?></td>
                                <td class="text-end">
                                    <?php if ($canManage): ?>
                                        <a href="kategori.php?edit=<?= e($row['id']) ?>" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a>
                                        <form method="post" class="d-inline" onsubmit="return confirmDelete('Hapus kategori ini?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= e($row['id']) ?>">
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$rows): ?><tr><td colspan="5" class="text-center text-muted">Belum ada data.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
