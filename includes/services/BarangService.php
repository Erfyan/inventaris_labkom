<?php

class BarangService {
    /**
     * Menyimpan barang baru atau memperbarui data barang yang sudah ada.
     */
    public static function simpan(array $payload, int $userId): int {
        $id = (int) ($payload['id'] ?? 0);
        $kode = strtoupper(trim($payload['kode_barang'] ?? ''));
        $nama = trim($payload['nama_barang'] ?? '');
        $kategoriId = (int) ($payload['kategori_id'] ?? 0) ?: null;
        $ruanganId = (int) ($payload['ruangan_id'] ?? 0) ?: null;
        $supplierId = (int) ($payload['supplier_id'] ?? 0) ?: null;
        $merk = trim($payload['merk'] ?? '');
        $spesifikasi = trim($payload['spesifikasi'] ?? '');
        $tahun = trim($payload['tahun_pengadaan'] ?? '') ?: null;
        $sumberDana = trim($payload['sumber_dana'] ?? '');
        $kondisi = $payload['kondisi'] ?? 'baik';
        $jumlah = max(0, (int) ($payload['jumlah'] ?? 0));
        $satuan = trim($payload['satuan'] ?? 'unit') ?: 'unit';
        $stokMinimum = max(0, (int) ($payload['stok_minimum'] ?? 1));
        $harga = (float) ($payload['harga'] ?? 0);
        $status = $payload['status'] ?? 'aktif';
        $keterangan = trim($payload['keterangan'] ?? '');

        if ($nama === '') {
            throw new RuntimeException('Nama barang wajib diisi.');
        }
        if (!in_array($kondisi, ['baik', 'rusak_ringan', 'rusak_berat', 'hilang'], true)) {
            throw new RuntimeException('Kondisi barang tidak valid.');
        }
        if (!in_array($status, ['aktif', 'nonaktif'], true)) {
            throw new RuntimeException('Status barang tidak valid.');
        }
        if ($kode === '') {
            $kode = generate_code('BRG', 'barang', 'kode_barang');
        }

        db()->beginTransaction();
        try {
            if ($id > 0) {
                $stmtOld = db()->prepare('SELECT jumlah FROM barang WHERE id=? FOR UPDATE');
                $stmtOld->execute([$id]);
                $oldJumlah = (int) $stmtOld->fetchColumn();

                $stmt = db()->prepare(
                    'UPDATE barang SET kode_barang=?, nama_barang=?, kategori_id=?, ruangan_id=?, supplier_id=?, merk=?, spesifikasi=?, tahun_pengadaan=?, sumber_dana=?, kondisi=?, jumlah=?, satuan=?, stok_minimum=?, harga=?, status=?, keterangan=? WHERE id=?'
                );
                $stmt->execute([
                    $kode, $nama, $kategoriId, $ruanganId, $supplierId, $merk, $spesifikasi,
                    $tahun, $sumberDana, $kondisi, $jumlah, $satuan, $stokMinimum, $harga, $status, $keterangan, $id
                ]);

                if ($jumlah !== $oldJumlah) {
                    $diff = $jumlah - $oldJumlah;
                    $stmtLog = db()->prepare(
                        'INSERT INTO stok_log (barang_id, tanggal, jenis, uraian, jumlah_masuk, jumlah_keluar, user_id) VALUES (?, CURDATE(), ?, ?, ?, ?, ?)'
                    );
                    $stmtLog->execute([
                        $id, 'penyesuaian', 'Penyesuaian stok dari perubahan form barang', max(0, $diff), max(0, -$diff), $userId
                    ]);
                }

                log_audit('update', 'barang', $id, $nama);
                db()->commit();
                return $id;
            } else {
                $stmt = db()->prepare(
                    'INSERT INTO barang (kode_barang, nama_barang, kategori_id, ruangan_id, supplier_id, merk, spesifikasi, tahun_pengadaan, sumber_dana, kondisi, jumlah, satuan, stok_minimum, harga, status, keterangan, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $kode, $nama, $kategoriId, $ruanganId, $supplierId, $merk, $spesifikasi,
                    $tahun, $sumberDana, $kondisi, $jumlah, $satuan, $stokMinimum, $harga, $status, $keterangan, $userId
                ]);
                $newId = (int) db()->lastInsertId();

                if ($jumlah > 0) {
                    $stmtLog = db()->prepare(
                        'INSERT INTO stok_log (barang_id, tanggal, jenis, uraian, jumlah_masuk, jumlah_keluar, user_id) VALUES (?, CURDATE(), ?, ?, ?, 0, ?)'
                    );
                    $stmtLog->execute([$newId, 'masuk', 'Stok awal barang inventaris', $jumlah, $userId]);
                }

                log_audit('create', 'barang', $newId, $nama);
                db()->commit();
                return $newId;
            }
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Menghapus record barang dari sistem.
     */
    public static function hapus(int $id): void {
        $stmt = db()->prepare('DELETE FROM barang WHERE id=?');
        $stmt->execute([$id]);
        log_audit('delete', 'barang', $id);
    }

    /**
     * Mengambil seluruh data barang dengan relasi kategori, ruangan, dan supplier.
     */
    public static function ambilSemua(?int $kategoriId = null, ?int $ruanganId = null, ?string $kondisi = null, ?string $keyword = null): array {
        $sql = "SELECT b.*, k.nama AS kategori, r.nama AS ruangan, s.nama AS supplier
                FROM barang b
                LEFT JOIN kategori k ON k.id=b.kategori_id
                LEFT JOIN ruangan r ON r.id=b.ruangan_id
                LEFT JOIN supplier s ON s.id=b.supplier_id
                WHERE 1=1";
        $params = [];

        if ($kategoriId) {
            $sql .= " AND b.kategori_id = ?";
            $params[] = $kategoriId;
        }
        if ($ruanganId) {
            $sql .= " AND b.ruangan_id = ?";
            $params[] = $ruanganId;
        }
        if ($kondisi) {
            $sql .= " AND b.kondisi = ?";
            $params[] = $kondisi;
        }
        if ($keyword) {
            $sql .= " AND (b.nama_barang LIKE ? OR b.kode_barang LIKE ? OR b.merk LIKE ?)";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
            $params[] = "%{$keyword}%";
        }

        $sql .= " ORDER BY b.id DESC";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Mengambil daftar barang yang stoknya berada di bawah atau sama dengan stok minimum.
     */
    public static function ambilStokMenipis(int $limit = 8): array {
        $limit = max(1, $limit);
        $stmt = db()->prepare(
            "SELECT b.*, k.nama AS kategori, r.nama AS ruangan
             FROM barang b
             LEFT JOIN kategori k ON k.id=b.kategori_id
             LEFT JOIN ruangan r ON r.id=b.ruangan_id
             WHERE b.status='aktif' AND b.jumlah <= b.stok_minimum
             ORDER BY b.jumlah ASC LIMIT {$limit}"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Mengambil ringkasan data statistik inventaris untuk dashboard.
     */
    public static function ambilStatistik(): array {
        $totalBarang = (int) db()->query("SELECT COUNT(*) FROM barang WHERE status = 'aktif'")->fetchColumn();
        $totalStok = (int) db()->query("SELECT COALESCE(SUM(jumlah), 0) FROM barang WHERE status = 'aktif'")->fetchColumn();
        $totalRusak = (int) db()->query("SELECT COUNT(*) FROM barang WHERE kondisi IN ('rusak_ringan','rusak_berat') AND status = 'aktif'")->fetchColumn();
        $totalDipinjam = (int) db()->query("SELECT COUNT(*) FROM peminjaman WHERE status IN ('dipinjam','terlambat')")->fetchColumn();
        $estimasiNilai = (float) db()->query("SELECT COALESCE(SUM(jumlah * harga), 0) FROM barang WHERE status = 'aktif'")->fetchColumn();

        return [
            'total_barang' => $totalBarang,
            'total_stok' => $totalStok,
            'total_rusak' => $totalRusak,
            'total_dipinjam' => $totalDipinjam,
            'estimasi_nilai' => $estimasiNilai,
        ];
    }

    /**
     * Mengambil rekap barang per kategori untuk dashboard.
     */
    public static function ambilRekapKategori(int $limit = 8): array {
        $limit = max(1, $limit);
        $stmt = db()->prepare(
            "SELECT COALESCE(k.nama, 'Tanpa Kategori') AS kategori, COUNT(b.id) AS total, COALESCE(SUM(b.jumlah), 0) AS stok
             FROM barang b
             LEFT JOIN kategori k ON k.id=b.kategori_id
             WHERE b.status='aktif'
             GROUP BY k.nama
             ORDER BY total DESC LIMIT {$limit}"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
