<?php

class MutasiService {
    /**
     * Memproses mutasi perpindahan barang antar ruangan laboratorium.
     */
    public static function prosesMutasi(int $barangId, int $tujuanId, string $tanggal, string $keterangan, int $userId): void {
        if ($barangId <= 0 || $tujuanId <= 0) {
            throw new RuntimeException('Barang dan ruangan tujuan wajib dipilih.');
        }

        db()->beginTransaction();
        try {
            $stmt = db()->prepare('SELECT * FROM barang WHERE id=? FOR UPDATE');
            $stmt->execute([$barangId]);
            $barang = $stmt->fetch();

            if (!$barang) {
                throw new RuntimeException('Barang tidak ditemukan.');
            }

            $asalId = $barang['ruangan_id'];
            if ((int) $asalId === $tujuanId) {
                throw new RuntimeException('Ruangan tujuan tidak boleh sama dengan ruangan asal.');
            }

            $stmt = db()->prepare(
                'INSERT INTO mutasi (barang_id, ruangan_asal_id, ruangan_tujuan_id, tanggal, keterangan, user_id)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$barangId, $asalId, $tujuanId, $tanggal, $keterangan, $userId]);
            $mutasiId = (int) db()->lastInsertId();

            $stmt = db()->prepare('UPDATE barang SET ruangan_id=? WHERE id=?');
            $stmt->execute([$tujuanId, $barangId]);

            $stmt = db()->prepare(
                'INSERT INTO stok_log (barang_id, tanggal, jenis, uraian, jumlah_masuk, jumlah_keluar, user_id)
                 VALUES (?, ?, ?, ?, 0, 0, ?)'
            );
            $stmt->execute([$barangId, $tanggal, 'mutasi', 'Mutasi ruangan. ' . $keterangan, $userId]);

            log_audit('create', 'mutasi', $mutasiId, $barang['nama_barang']);
            db()->commit();
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Mengambil riwayat mutasi ruangan.
     */
    public static function ambilRiwayat(int $limit = 100): array {
        $limit = max(1, $limit);
        $stmt = db()->prepare(
            "SELECT m.*, b.kode_barang, b.nama_barang, ra.nama AS ruangan_asal, rt.nama AS ruangan_tujuan, u.nama AS user_nama
             FROM mutasi m
             JOIN barang b ON b.id=m.barang_id
             LEFT JOIN ruangan ra ON ra.id=m.ruangan_asal_id
             LEFT JOIN ruangan rt ON rt.id=m.ruangan_tujuan_id
             LEFT JOIN users u ON u.id=m.user_id
             ORDER BY m.created_at DESC LIMIT {$limit}"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
