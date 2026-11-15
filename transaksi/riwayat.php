<?php
require __DIR__ . '/../includes/auth.php';

$page_title = 'Riwayat Peminjaman';
include __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/koneksi.php';

$rawAnggotaId = $_GET['anggota_id'] ?? '';
$anggotaId = is_string($rawAnggotaId) ? filter_var($rawAnggotaId, FILTER_VALIDATE_INT) : false;
$invalidAnggotaId = $rawAnggotaId !== '' &&
    ($anggotaId === false || $anggotaId === null || $anggotaId < 1);
$daftarAnggota = $pdo->query('SELECT id, nama, no_anggota FROM anggota ORDER BY nama')
    ->fetchAll(PDO::FETCH_ASSOC);
$anggotaTerpilih = null;
$riwayat = [];

if ($anggotaId !== false && $anggotaId !== null && $anggotaId > 0) {
    $anggotaStmt = $pdo->prepare('SELECT id, nama, no_anggota FROM anggota WHERE id = :id');
    $anggotaStmt->execute(['id' => $anggotaId]);
    $anggotaTerpilih = $anggotaStmt->fetch(PDO::FETCH_ASSOC);

    if ($anggotaTerpilih) {
        $stmt = $pdo->prepare(
            'SELECT p.id, b.judul, p.tanggal_pinjam, p.tanggal_jatuh_tempo,
                    p.tanggal_kembali, p.status
             FROM peminjaman p
             JOIN buku b ON b.id = p.buku_id
             WHERE p.anggota_id = :id
             ORDER BY p.tanggal_pinjam DESC, p.id DESC'
        );
        $stmt->execute(['id' => $anggotaId]);
        $riwayat = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<section>
    <h2>Riwayat Peminjaman</h2>

    <form method="get" action="riwayat.php">
        <p>
            <label for="anggota_id">Pilih Anggota</label>
            <select id="anggota_id" name="anggota_id" required>
                <option value="">-- Pilih Anggota --</option>
                <?php foreach ($daftarAnggota as $anggota): ?>
                    <option value="<?php echo (int) $anggota['id']; ?>"
                        <?php echo $anggotaTerpilih && (int) $anggotaTerpilih['id'] === (int) $anggota['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($anggota['nama'], ENT_QUOTES, 'UTF-8'); ?>
                        (<?php echo htmlspecialchars($anggota['no_anggota'], ENT_QUOTES, 'UTF-8'); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p><button type="submit" class="btn btn-primary">Tampilkan Riwayat</button></p>
    </form>

    <?php if ($invalidAnggotaId || ($anggotaId && !$anggotaTerpilih)): ?>
        <p class="flash flash-error">Anggota tidak ditemukan.</p>
    <?php endif; ?>

    <?php if ($anggotaTerpilih): ?>
        <h3>Riwayat — <?php echo htmlspecialchars($anggotaTerpilih['nama'], ENT_QUOTES, 'UTF-8'); ?></h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID Transaksi</th>
                        <th>Buku</th>
                        <th>Tanggal Pinjam</th>
                        <th>Batas Kembali</th>
                        <th>Tanggal Kembali</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($riwayat)): ?>
                        <tr><td colspan="6">Belum ada riwayat peminjaman.</td></tr>
                    <?php else: ?>
                        <?php foreach ($riwayat as $transaksi): ?>
                            <tr>
                                <td>TR<?php echo (int) $transaksi['id']; ?></td>
                                <td><?php echo htmlspecialchars($transaksi['judul'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($transaksi['tanggal_pinjam'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($transaksi['tanggal_jatuh_tempo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($transaksi['tanggal_kembali'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo $transaksi['status'] === 'dipinjam' ? 'Dipinjam' : 'Selesai'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
