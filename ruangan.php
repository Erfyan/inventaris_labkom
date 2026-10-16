<?php
$pageTitle = 'Ruangan / Laboratorium';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_login();

$canManage = can_manage_data();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    if (!$canManage) {
        set_flash('danger', 'Anda tidak memiliki hak untuk mengubah data.');
        redirect('ruangan.php');
    }

    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save') {
            $id = (int) ($_POST['id'] ?? 0);
            $kode = strtoupper(trim($_POST['kode'] ?? ''));
            $nama = trim($_POST['nama'] ?? '');
            $penanggungJawab = trim($_POST['penanggung_jawab'] ?? '');
            $lokasi = trim($_POST['lokasi'] ?? '');

            if ($nama === '') {
                throw new RuntimeException('Nama ruangan wajib diisi.');
            }

            if ($id > 0) {
                $stmt = db()->prepare('UPDATE ruangan SET kode=?, nama=?, penanggung_jawab=?, lokasi=? WHERE id=?');
                $stmt->execute([$kode, $nama, $penanggungJawab, $lokasi, $id]);
                log_audit('update', 'ruangan', $id, $nama);
                set_flash('success', 'Ruangan berhasil diperbarui.');
            } else {
                $stmt = db()->prepare('INSERT INTO ruangan (kode, nama, penanggung_jawab, lokasi) VALUES (?, ?, ?, ?)');
                $stmt->execute([$kode, $nama, $penanggungJawab, $lokasi]);
                log_audit('create', 'ruangan', (int) db()->lastInsertId(), $nama);
                set_flash('success', 'Ruangan berhasil ditambahkan.');
            }
        } elseif ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = db()->prepare('DELETE FROM ruangan WHERE id=?');
            $stmt->execute([$id]);
            log_audit('delete', 'ruangan', $id);
            set_flash('success', 'Ruangan berhasil dihapus.');
        }
    } catch (Throwable $e) {
        set_flash('danger', 'Gagal memproses data: ' . $e->getMessage());
    }
    redirect('ruangan.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = db()->prepare('SELECT * FROM ruangan WHERE id=?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit = $stmt->fetch();
}

$rows = db()->query('SELECT r.*, COUNT(b.id) AS total_barang FROM ruangan r LEFT JOIN barang b ON b.ruangan_id=r.id GROUP BY r.id ORDER BY r.nama')->fetchAll();
require_once __DIR__ . '/includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-4 <?php if (!$canManage) echo 'd-none'; ?>">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0"><?= $edit ? 'Edit Ruangan' : 'Tambah Ruangan' ?></h2></div>
            <div class="card-body px-4 pb-4">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
                    <div class="mb-3"><label>Kode</label><input type="text" name="kode" class="form-control" value="<?= e($edit['kode'] ?? '') ?>" placeholder="LAB-KOM"></div>
                    <div class="mb-3"><label class="required">Nama Ruangan</label><input type="text" name="nama" class="form-control" required value="<?= e($edit['nama'] ?? '') ?>"></div>
                    <div class="mb-3"><label>Penanggung Jawab</label><input type="text" name="penanggung_jawab" class="form-control" value="<?= e($edit['penanggung_jawab'] ?? '') ?>"></div>
                    <div class="mb-3"><label>Lokasi</label><input type="text" name="lokasi" class="form-control" value="<?= e($edit['lokasi'] ?? '') ?>"></div>
                    <button class="btn btn-primary"><i class="bi bi-save mr-1"></i>Simpan</button>
                    <?php if ($edit): ?><a href="ruangan.php" class="btn btn-light border">Batal</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="<?= $canManage ? 'col-lg-8' : 'col-12' ?>">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4"><h2 class="h5 mb-0">Daftar Ruangan / Lab</h2></div>
            <div class="card-body px-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Kode</th><th>Nama</th><th>PJ</th><th>Lokasi</th><th>Total Barang</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= e($row['kode']) ?></td><td><?= e($row['nama']) ?></td><td><?= e($row['penanggung_jawab']) ?></td><td><?= e($row['lokasi']) ?></td><td><?= e($row['total_barang']) ?></td>
                                <td class="text-end">
                                    <?php if ($canManage): ?>
                                        <a href="ruangan.php?edit=<?= e($row['id']) ?>" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a>
                                        <form method="post" class="d-inline" onsubmit="return confirmDelete('Hapus ruangan ini?')">
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
