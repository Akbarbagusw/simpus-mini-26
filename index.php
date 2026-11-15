<?php
$page_title = "Beranda";
include __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/koneksi.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$totalBuku = $pdo->query("SELECT COUNT(*) FROM buku")->fetchColumn();
$totalAnggota = $pdo->query("SELECT COUNT(*) FROM anggota")->fetchColumn();
$totalPeminjamanAktif = 0;
$peminjamanTerbaru = [];

if ($sudahLogin) {
    $totalPeminjamanAktif = (int) $pdo->query("SELECT COUNT(*) FROM peminjaman WHERE status = 'dipinjam'")->fetchColumn();
    $peminjamanTerbaru = $pdo->query(
        "SELECT p.id, p.tanggal_pinjam, p.status, a.nama, b.judul
         FROM peminjaman p
         JOIN anggota a ON a.id = p.anggota_id
         JOIN buku b ON b.id = p.buku_id
         ORDER BY p.id DESC
         LIMIT 5"
    )->fetchAll(PDO::FETCH_ASSOC);
}
?>
<section class="dashboard-hero">
    <p class="dashboard-eyebrow">PUSAT LAYANAN PERPUSTAKAAN</p>
    <?php if ($sudahLogin): ?>
        <h2>Selamat datang, <?php echo htmlspecialchars((string) $userName, ENT_QUOTES, 'UTF-8'); ?></h2>
        <p>Aplikasi sederhana untuk mengelola data buku dan anggota perpustakaan.</p>
    <?php else: ?>
        <h2>Temukan bacaan dan kelola koleksi perpustakaan</h2>
        <p>Jelajahi katalog SIMPUS-Mini dan temukan buku yang Anda butuhkan.</p>
        <a href="buku/list.php" class="btn btn-hero">Jelajahi Katalog <span aria-hidden="true">&rarr;</span></a>
    <?php endif; ?>
</section>

<?php if ($flash): ?>
    <section>
        <p class="flash flash-<?php echo $flash['type']; ?>"><?php echo htmlspecialchars($flash['pesan']); ?></p>
    </section>
<?php endif; ?>

<section class="dashboard-stats">
    <h2>Ringkasan</h2>
    <article class="stat-card">
        <h3>Total Buku</h3>
        <p><?php echo $totalBuku; ?></p>
    </article>
    <article class="stat-card">
        <h3>Total Anggota</h3>
        <p><?php echo $totalAnggota; ?></p>
    </article>
    <?php if ($sudahLogin): ?>
        <article class="stat-card">
            <h3>Sedang Dipinjam</h3>
            <p><?php echo $totalPeminjamanAktif; ?></p>
        </article>
    <?php endif; ?>
</section>

<?php if ($sudahLogin): ?>
    <section>
        <h2>Aksi Peminjaman</h2>
        <p class="dashboard-actions">
            <a href="transaksi/tambah.php" class="btn btn-primary">+ Peminjaman Baru</a>
            <a href="transaksi/kembali.php" class="btn btn-secondary">Pengembalian Buku</a>
            <a href="transaksi/riwayat.php" class="btn btn-secondary">Riwayat Peminjaman</a>
        </p>
    </section>

    <section>
        <h2>Transaksi Peminjaman Terbaru</h2>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID Transaksi</th>
                        <th>Anggota</th>
                        <th>Buku</th>
                        <th>Tanggal Pinjam</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($peminjamanTerbaru)): ?>
                        <tr><td colspan="5">Belum ada transaksi peminjaman.</td></tr>
                    <?php else: ?>
                        <?php foreach ($peminjamanTerbaru as $transaksi): ?>
                            <tr>
                                <td>TR<?php echo (int) $transaksi['id']; ?></td>
                                <td><?php echo htmlspecialchars($transaksi['nama'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($transaksi['judul'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($transaksi['tanggal_pinjam'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo $transaksi['status'] === 'dipinjam' ? 'Dipinjam' : 'Selesai'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>