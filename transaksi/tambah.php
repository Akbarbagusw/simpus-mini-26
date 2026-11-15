<?php
require __DIR__ . '/../includes/auth.php';

$page_title = 'Peminjaman Baru';
include __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/koneksi.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$daftarAnggota = $pdo->query(
    "SELECT a.id, a.nama, a.no_anggota, COUNT(p.id) AS pinjaman_aktif
     FROM anggota a
     LEFT JOIN peminjaman p ON p.anggota_id = a.id AND p.status = 'dipinjam'
     GROUP BY a.id
     ORDER BY a.nama"
)->fetchAll(PDO::FETCH_ASSOC);
$daftarBuku = $pdo->query('SELECT id, judul, stok FROM buku WHERE stok > 0 ORDER BY judul')
    ->fetchAll(PDO::FETCH_ASSOC);
?>
<section>
    <h2>Peminjaman Buku Baru</h2>

    <?php if ($flash): ?>
        <p class="flash flash-<?php echo htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($flash['pesan'], ENT_QUOTES, 'UTF-8'); ?>
        </p>
    <?php endif; ?>

    <?php if (empty($daftarAnggota)): ?>
        <p class="flash flash-error">Belum ada anggota. Tambahkan anggota sebelum membuat peminjaman.</p>
    <?php elseif (empty($daftarBuku)): ?>
        <p class="flash flash-error">Tidak ada buku dengan stok tersedia.</p>
    <?php else: ?>
        <form method="post" action="proses_tambah.php">
            <p>
                <label for="anggota_id">Anggota</label>
                <select id="anggota_id" name="anggota_id" required>
                    <option value="">-- Pilih Anggota --</option>
                    <?php foreach ($daftarAnggota as $anggota): ?>
                        <?php $pinjamanAktif = (int) $anggota['pinjaman_aktif']; ?>
                        <option value="<?php echo (int) $anggota['id']; ?>"
                            <?php echo $pinjamanAktif >= 3 ? 'disabled' : ''; ?>>
                            <?php echo htmlspecialchars($anggota['nama'], ENT_QUOTES, 'UTF-8'); ?>
                            (<?php echo htmlspecialchars($anggota['no_anggota'], ENT_QUOTES, 'UTF-8'); ?>)
                            — <?php echo $pinjamanAktif; ?>/3 pinjaman aktif
                            <?php echo $pinjamanAktif >= 3 ? ' (kuota penuh)' : ''; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label for="buku_id">Buku (stok tersedia)</label>
                <select id="buku_id" name="buku_id" required>
                    <option value="">-- Pilih Buku --</option>
                    <?php foreach ($daftarBuku as $buku): ?>
                        <option value="<?php echo (int) $buku['id']; ?>">
                            <?php echo htmlspecialchars($buku['judul'], ENT_QUOTES, 'UTF-8'); ?>
                            (stok: <?php echo (int) $buku['stok']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>Batas pengembalian: 7 hari setelah tanggal peminjaman.</p>
            <p>
                <button type="submit">Simpan Peminjaman</button>
                <a href="../index.php" class="btn btn-secondary">Batal</a>
            </p>
        </form>
    <?php endif; ?>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
