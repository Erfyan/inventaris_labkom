<?php

class PerawatanService {
    /**
     * Mencatat tiket perawatan atau perbaikan barang baru.
     */
    public static function tambah(array $payload, int $userId): int {
        $barangId = (int) ($payload['barang_id'] ?? 0);
        $tanggalMulai = $payload['tanggal_mulai'] ?? date('Y-m-d');
        $tanggalSelesai = !empty($payload['tanggal_selesai']) ? $payload['tanggal_selesai'] : null;
        $jenis = trim($payload['jenis'] ?? 'Perawatan rutin');
        $deskripsi = trim($payload['deskripsi'] ?? '');
        $biaya = (float) ($payload['biaya'] ?? 0);
        $teknisi = trim($payload['teknisi'] ?? '');
        $status = $payload['status'] ?? 'proses';

        if ($barangId <= 0) {
            throw new RuntimeException('Barang wajib dipilih.');
        }
        if (!in_array($status, ['proses', 'selesai', 'batal'], true)) {
            throw new RuntimeException('Status perawatan tidak valid.');
        }

        $stmt = db()->prepare(
            'INSERT INTO perawatan (barang_id, tanggal_mulai, tanggal_selesai, jenis, deskripsi, biaya, teknisi, status, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$barangId, $tanggalMulai, $tanggalSelesai, $jenis, $deskripsi, $biaya, $teknisi, $status, $userId]);
        $id = (int) db()->lastInsertId();

        log_audit('create', 'perawatan', $id, $jenis);
        return $id;
    }

    /**
     * Memperbarui status perawatan (proses, selesai, batal).
     */
    public static function updateStatus(int $id, string $status, int $userId): void {
        if (!in_array($status, ['proses', 'selesai', 'batal'], true)) {
            throw new RuntimeException('Status tidak valid.');
        }

        $tanggalSelesai = $status === 'selesai' ? date('Y-m-d') : null;
        $stmt = db()->prepare('UPDATE perawatan SET status=?, tanggal_selesai=COALESCE(tanggal_selesai, ?) WHERE id=?');
        $stmt->execute([$status, $tanggalSelesai, $id]);

        log_audit('update', 'perawatan', $id, 'Status diubah ke: ' . $status);
    }

    /**
     * Mengambil riwayat perawatan barang.
     */
    public static function ambilRiwayat(int $limit = 100): array {
        $limit = max(1, $limit);
        $stmt = db()->prepare(
            "SELECT p.*, b.kode_barang, b.nama_barang, u.nama AS user_nama
             FROM perawatan p
             JOIN barang b ON b.id=p.barang_id
             LEFT JOIN users u ON u.id=p.created_by
             ORDER BY p.created_at DESC LIMIT {$limit}"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
