<?php

class StokService {
    /**
     * Memproses transaksi mutasi stok (masuk, keluar, atau penyesuaian) secara atomik.
     */
    public static function prosesTransaksi(int $barangId, string $jenis, int $jumlahInput, string $tanggal, string $uraian, int $userId): void {
        if (!in_array($jenis, ['masuk', 'keluar', 'penyesuaian'], true)) {
            throw new RuntimeException('Jenis transaksi stok tidak valid.');
        }
        if ($barangId <= 0) {
            throw new RuntimeException('Barang wajib dipilih.');
        }

        db()->beginTransaction();
        try {
            $stmt = db()->prepare('SELECT * FROM barang WHERE id=? FOR UPDATE');
            $stmt->execute([$barangId]);
            $barang = $stmt->fetch();

            if (!$barang) {
                throw new RuntimeException('Barang tidak ditemukan.');
            }

            $jumlahMasuk = 0;
            $jumlahKeluar = 0;
            $stokBaru = (int) $barang['jumlah'];

            if ($jenis === 'masuk') {
                if ($jumlahInput <= 0) {
                    throw new RuntimeException('Jumlah masuk harus lebih dari 0.');
                }
                $jumlahMasuk = $jumlahInput;
                $stokBaru += $jumlahInput;
            } elseif ($jenis === 'keluar') {
                if ($jumlahInput <= 0) {
                    throw new RuntimeException('Jumlah keluar harus lebih dari 0.');
                }
                if ((int) $barang['jumlah'] < $jumlahInput) {
                    throw new RuntimeException('Stok barang tidak mencukupi (tersedia: ' . $barang['jumlah'] . ').');
                }
                $jumlahKeluar = $jumlahInput;
                $stokBaru -= $jumlahInput;
            } else { // penyesuaian
                $stokBaru = $jumlahInput;
                $diff = $stokBaru - (int) $barang['jumlah'];
                $jumlahMasuk = max(0, $diff);
                $jumlahKeluar = max(0, -$diff);
            }

            $stmt = db()->prepare('UPDATE barang SET jumlah=? WHERE id=?');
            $stmt->execute([$stokBaru, $barangId]);

            $stmt = db()->prepare(
                'INSERT INTO stok_log (barang_id, tanggal, jenis, uraian, jumlah_masuk, jumlah_keluar, user_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$barangId, $tanggal, $jenis, $uraian, $jumlahMasuk, $jumlahKeluar, $userId]);
            $logId = (int) db()->lastInsertId();

            log_audit('create', 'stok_log', $logId, $jenis . ' ' . $barang['nama_barang'] . ' (' . $jumlahInput . ')');

            db()->commit();
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Mengambil riwayat log transaksi stok terbaru atau berdasarkan rentang tanggal.
     */
    public static function ambilRiwayat(?string $from = null, ?string $to = null, int $limit = 100): array {
        if ($from && $to) {
            $stmt = db()->prepare(
                'SELECT l.*, b.kode_barang, b.nama_barang, u.nama AS user_nama
                 FROM stok_log l
                 JOIN barang b ON b.id=l.barang_id
                 LEFT JOIN users u ON u.id=l.user_id
                 WHERE l.tanggal BETWEEN ? AND ?
                 ORDER BY l.tanggal DESC, l.id DESC'
            );
            $stmt->execute([$from, $to]);
            return $stmt->fetchAll();
        }

        $limit = max(1, $limit);
        $stmt = db()->prepare(
            "SELECT l.*, b.kode_barang, b.nama_barang, u.nama AS user_nama
             FROM stok_log l
             JOIN barang b ON b.id=l.barang_id
             LEFT JOIN users u ON u.id=l.user_id
             ORDER BY l.created_at DESC, l.id DESC
             LIMIT {$limit}"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
