<?php

class PeminjamanService {
    /**
     * Memperbarui status peminjaman yang telah melewati jatuh tempo menjadi 'terlambat'.
     */
    public static function sinkronisasiStatusTerlambat(): void {
        db()->query("UPDATE peminjaman SET status = 'terlambat' WHERE status = 'dipinjam' AND tanggal_rencana_kembali < CURDATE()");
    }

    /**
     * Membuat data peminjaman baru beserta detail dan pengurangan stok secara atomik.
     */
    public static function buatPeminjaman(array $payload, int $userId): string {
        $namaPeminjam = trim($payload['nama_peminjam'] ?? '');
        $kelasJurusan = trim($payload['kelas_jurusan'] ?? '');
        $noHp = trim($payload['no_hp'] ?? '');
        $keperluan = trim($payload['keperluan'] ?? '');
        $tanggalPinjam = $payload['tanggal_pinjam'] ?? date('Y-m-d');
        $tanggalRencana = $payload['tanggal_rencana_kembali'] ?? date('Y-m-d');
        $catatan = trim($payload['catatan'] ?? '');
        $barangIds = $payload['barang_id'] ?? [];
        $jumlahs = $payload['jumlah'] ?? [];

        if ($namaPeminjam === '') {
            throw new RuntimeException('Nama peminjam wajib diisi.');
        }

        $items = [];
        foreach ($barangIds as $i => $barangId) {
            $barangId = (int) $barangId;
            $jumlah = max(0, (int) ($jumlahs[$i] ?? 0));
            if ($barangId > 0 && $jumlah > 0) {
                $items[$barangId] = ($items[$barangId] ?? 0) + $jumlah;
            }
        }

        if (empty($items)) {
            throw new RuntimeException('Minimal pilih satu barang dengan jumlah lebih dari 0.');
        }

        db()->beginTransaction();
        try {
            $kode = generate_code('PJM', 'peminjaman', 'kode_pinjam');
            $stmt = db()->prepare(
                'INSERT INTO peminjaman (kode_pinjam, tanggal_pinjam, tanggal_rencana_kembali, nama_peminjam, kelas_jurusan, no_hp, keperluan, status, catatan, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $kode,
                $tanggalPinjam,
                $tanggalRencana,
                $namaPeminjam,
                $kelasJurusan,
                $noHp,
                $keperluan,
                'dipinjam',
                $catatan,
                $userId
            ]);
            $peminjamanId = (int) db()->lastInsertId();

            foreach ($items as $barangId => $jumlah) {
                $stmt = db()->prepare('SELECT * FROM barang WHERE id=? FOR UPDATE');
                $stmt->execute([$barangId]);
                $barang = $stmt->fetch();

                if (!$barang) {
                    throw new RuntimeException('Barang dengan ID #' . $barangId . ' tidak ditemukan.');
                }
                if ((int) $barang['jumlah'] < $jumlah) {
                    throw new RuntimeException('Stok tidak cukup untuk: ' . $barang['nama_barang'] . ' (tersisa ' . $barang['jumlah'] . ').');
                }

                $stmt = db()->prepare('INSERT INTO peminjaman_detail (peminjaman_id, barang_id, jumlah, kondisi_pinjam) VALUES (?, ?, ?, ?)');
                $stmt->execute([$peminjamanId, $barangId, $jumlah, $barang['kondisi']]);

                $stmt = db()->prepare('UPDATE barang SET jumlah = jumlah - ? WHERE id=?');
                $stmt->execute([$jumlah, $barangId]);

                $stmt = db()->prepare('INSERT INTO stok_log (barang_id, tanggal, jenis, uraian, jumlah_masuk, jumlah_keluar, user_id) VALUES (?, ?, ?, ?, 0, ?, ?)');
                $stmt->execute([$barangId, $tanggalPinjam, 'keluar', 'Peminjaman ' . $kode . ' oleh ' . $namaPeminjam, $jumlah, $userId]);
            }

            log_audit('create', 'peminjaman', $peminjamanId, $kode);
            db()->commit();

            return $kode;
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Memproses pengembalian peminjaman dan mengembalikan kuantitas stok barang.
     */
    public static function kembalikanPeminjaman(int $id, string $tanggalKembali, string $kondisiKembali, string $catatan, int $userId): void {
        db()->beginTransaction();
        try {
            $stmt = db()->prepare('SELECT * FROM peminjaman WHERE id=? FOR UPDATE');
            $stmt->execute([$id]);
            $peminjaman = $stmt->fetch();

            if (!$peminjaman) {
                throw new RuntimeException('Peminjaman tidak ditemukan.');
            }
            if (!in_array($peminjaman['status'], ['dipinjam', 'terlambat'], true)) {
                throw new RuntimeException('Peminjaman sudah selesai atau tidak aktif.');
            }

            $stmt = db()->prepare('SELECT d.*, b.nama_barang FROM peminjaman_detail d JOIN barang b ON b.id=d.barang_id WHERE d.peminjaman_id=?');
            $stmt->execute([$id]);
            $details = $stmt->fetchAll();

            foreach ($details as $detail) {
                $stmt = db()->prepare('UPDATE barang SET jumlah = jumlah + ? WHERE id=?');
                $stmt->execute([$detail['jumlah'], $detail['barang_id']]);

                $stmt = db()->prepare('UPDATE peminjaman_detail SET kondisi_kembali=? WHERE id=?');
                $stmt->execute([$kondisiKembali, $detail['id']]);

                $stmt = db()->prepare('INSERT INTO stok_log (barang_id, tanggal, jenis, uraian, jumlah_masuk, jumlah_keluar, user_id) VALUES (?, ?, ?, ?, ?, 0, ?)');
                $stmt->execute([
                    $detail['barang_id'],
                    $tanggalKembali,
                    'masuk',
                    'Pengembalian ' . $peminjaman['kode_pinjam'] . ' oleh ' . $peminjaman['nama_peminjam'],
                    $detail['jumlah'],
                    $userId
                ]);
            }

            $stmt = db()->prepare('UPDATE peminjaman SET status="dikembalikan", tanggal_kembali=?, catatan=CONCAT(COALESCE(catatan, ""), ?) WHERE id=?');
            $stmt->execute([$tanggalKembali, "\nPengembalian: " . $catatan, $id]);

            log_audit('return', 'peminjaman', $id, $peminjaman['kode_pinjam']);
            db()->commit();
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Membatalkan peminjaman aktif dan mengembalikan stok barang yang telah terpotong.
     */
    public static function batalkanPeminjaman(int $id, int $userId): void {
        db()->beginTransaction();
        try {
            $stmt = db()->prepare('SELECT * FROM peminjaman WHERE id=? FOR UPDATE');
            $stmt->execute([$id]);
            $peminjaman = $stmt->fetch();

            if (!$peminjaman) {
                throw new RuntimeException('Peminjaman tidak ditemukan.');
            }
            if (!in_array($peminjaman['status'], ['dipinjam', 'terlambat'], true)) {
                throw new RuntimeException('Hanya peminjaman berstatus dipinjam/terlambat yang dapat dibatalkan.');
            }

            $stmt = db()->prepare('SELECT d.*, b.nama_barang FROM peminjaman_detail d JOIN barang b ON b.id=d.barang_id WHERE d.peminjaman_id=?');
            $stmt->execute([$id]);
            $details = $stmt->fetchAll();

            foreach ($details as $detail) {
                $stmt = db()->prepare('UPDATE barang SET jumlah = jumlah + ? WHERE id=?');
                $stmt->execute([$detail['jumlah'], $detail['barang_id']]);

                $stmt = db()->prepare('INSERT INTO stok_log (barang_id, tanggal, jenis, uraian, jumlah_masuk, jumlah_keluar, user_id) VALUES (?, CURDATE(), ?, ?, ?, 0, ?)');
                $stmt->execute([
                    $detail['barang_id'],
                    'penyesuaian',
                    'Pembatalan peminjaman ' . $peminjaman['kode_pinjam'],
                    $detail['jumlah'],
                    $userId
                ]);
            }

            $stmt = db()->prepare('UPDATE peminjaman SET status="dibatalkan" WHERE id=?');
            $stmt->execute([$id]);

            log_audit('cancel', 'peminjaman', $id, $peminjaman['kode_pinjam']);
            db()->commit();
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Mengambil daftar peminjaman terbaru untuk keperluan ringkasan dashboard.
     */
    public static function ambilPeminjamanTerbaru(int $limit = 8): array {
        $limit = max(1, $limit);
        $stmt = db()->prepare("SELECT p.*, u.nama AS petugas FROM peminjaman p LEFT JOIN users u ON u.id=p.created_by ORDER BY p.created_at DESC LIMIT {$limit}");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
