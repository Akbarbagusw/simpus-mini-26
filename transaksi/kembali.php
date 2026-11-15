<?php
require __DIR__ . '/../includes/auth.php';

$page_title = 'Pengembalian Buku';
include __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/koneksi.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$rawKeyword = $_GET['q'] ?? '';
$keyword = is_string($rawKeyword) ? trim($rawKeyword) : '';
$sql = "SELECT p.id, p.tanggal_pinjam, p.tanggal_jatuh_tempo,
               a.nama, a.no_anggota, b.judul,
               GREATEST(CURRENT_DATE - p.tanggal_jatuh_tempo, 0) AS hari_terlambat
        FROM peminjaman p
        JOIN anggota a ON a.id = p.anggota_id
        JOIN buku b ON b.id = p.buku_id
        WHERE p.status = 'dipinjam'";

if ($keyword !== '') {
    $sql .= " AND (CAST(p.id AS TEXT) ILIKE :id
                   OR a.nama ILIKE :nama
                   OR a.no_anggota ILIKE :no_anggota
                   OR b.judul ILIKE :judul)";
}
$sql .= ' ORDER BY p.tanggal_pinjam, p.id';
$stmt = $pdo->prepare($sql);
if ($keyword !== '') {
    $pattern = '%' . $keyword . '%';
    $stmt->execute([
        'id' => $pattern,
        'nama' => $pattern,
        'no_anggota' => $pattern,
        'judul' => $pattern,
    ]);
} else {
    $stmt->execute();
}
$daftarAktif = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<section>
    <h2>Pengembalian Buku</h2>

    <?php if ($flash): ?>
        <p class="flash flash-<?php echo htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($flash['pesan'], ENT_QUOTES, 'UTF-8'); ?>
        </p>
    <?php endif; ?>

    <form method="get" action="kembali.php" class="search-box">
        <label for="search-input">Cari transaksi, anggota, atau buku</label>
        <input type="search" id="search-input" name="q" value="<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>"
            placeholder="ID transaksi, nama anggota, atau judul buku...">
        <button type="submit" class="btn btn-primary">Cari</button>
        <?php if ($keyword !== ''): ?>
            <a href="kembali.php" class="btn btn-secondary">Reset</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Anggota</th>
                    <th>Buku</th>
                    <th>Tanggal Pinjam</th>
                    <th>Batas Kembali</th>
                    <th>Keterlambatan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($daftarAktif)): ?>
                    <tr><td colspan="7">Tidak ada peminjaman aktif yang cocok.</td></tr>
                <?php else: ?>
                    <?php foreach ($daftarAktif as $transaksi): ?>
                        <?php $hariTerlambat = (int) $transaksi['hari_terlambat']; ?>
                        <tr>
                            <td>TR<?php echo (int) $transaksi['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($transaksi['nama'], ENT_QUOTES, 'UTF-8'); ?>
                                (<?php echo htmlspecialchars($transaksi['no_anggota'], ENT_QUOTES, 'UTF-8'); ?>)
                            </td>
                            <td><?php echo htmlspecialchars($transaksi['judul'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($transaksi['tanggal_pinjam'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($transaksi['tanggal_jatuh_tempo'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php if ($hariTerlambat > 0): ?>
                                    <?php echo $hariTerlambat; ?> hari
                                    (denda indikatif Rp <?php echo number_format($hariTerlambat * 1000, 0, ',', '.'); ?>)
                                <?php else: ?>
                                    Belum terlambat
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" action="proses_kembali.php">
                                    <input type="hidden" name="id" value="<?php echo (int) $transaksi['id']; ?>">
                                    <button type="submit" class="btn btn-primary">Kembalikan</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
