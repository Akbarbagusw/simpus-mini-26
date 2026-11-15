<?php
require __DIR__ . '/../includes/auth.php';

$page_title = "Daftar Anggota";
include __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/koneksi.php';

$roleStmt = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
$roleStmt->execute(['id' => (int) $_SESSION['user_id']]);
$userRole = strtolower(trim((string) $roleStmt->fetchColumn()));
$canDelete = in_array($userRole, ['admin', 'petugas'], true);
$_SESSION['role'] = $userRole;

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$rawKeyword = $_GET['q'] ?? '';
$keyword = is_string($rawKeyword) ? trim($rawKeyword) : '';
if ($keyword !== '') {
    $searchPattern = '%' . $keyword . '%';
    $stmt = $pdo->prepare(
        'SELECT * FROM anggota
         WHERE no_anggota ILIKE :no_anggota OR nama ILIKE :nama
         ORDER BY id DESC'
    );
    $stmt->execute([
        'no_anggota' => $searchPattern,
        'nama' => $searchPattern,
    ]);
    $daftarAnggota = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $daftarAnggota = $pdo->query("SELECT * FROM anggota ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
}
?>
<section>
    <h2>Daftar Anggota</h2>

    <?php if ($flash): ?>
        <p class="flash flash-<?php echo $flash['type']; ?>"><?php echo htmlspecialchars($flash['pesan']); ?></p>
    <?php endif; ?>

    <form id="member-search-form" method="get" action="list.php" data-search-url="cari.php" data-can-delete="<?php echo $canDelete ? 'true' : 'false'; ?>" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
        <div class="search-box" style="flex: 1; min-width: 250px; display: flex; gap: 0.5rem; align-items: flex-end;">
            <div class="search-input-wrap">
                <label for="search-input">Cari Nama / No. Anggota</label>
                <input type="search" id="search-input" name="q" placeholder="Ketik nama atau nomor anggota..." value="<?php echo htmlspecialchars($keyword); ?>" aria-controls="member-results">
            </div>
            <button type="submit" class="btn btn-primary" style="padding: 0.55rem 1rem;">Cari</button>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <a href="tambah.php" class="btn btn-primary">+ Tambah Anggota</a>
        </div>
    </form>

    <p id="member-search-status" role="status" aria-live="polite"></p>

    <div class="table-responsive" id="member-results">
        <table>
            <thead>
                <tr>
                    <th>No. Anggota</th>
                    <th>Nama Lengkap</th>
                    <th>No. HP</th>
                    <th>Alamat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="member-results-body">
                <?php if (empty($daftarAnggota)): ?>
                    <tr>
                        <td colspan="5"><?php echo $keyword !== '' ? 'Tidak ada anggota yang cocok dengan pencarian.' : 'Belum ada data anggota. Silakan tambah lewat menu "Tambah Anggota".'; ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($daftarAnggota as $anggota): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($anggota['no_anggota'] ?? '-'); ?></code></td>
                            <td>
                                <strong><?php echo htmlspecialchars($anggota['nama'] ?? '-'); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($anggota['no_hp'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($anggota['alamat'] ?? '-'); ?></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                                    <a href="ubah.php?id=<?php echo (int) $anggota['id']; ?>" class="btn btn-secondary" style="padding: 0.45rem 0.75rem;">Edit</a>
                                    <?php if ($canDelete): ?>
                                        <form class="form-hapus" action="hapus.php" method="post" style="display: inline; margin: 0;">
                                            <input type="hidden" name="id" value="<?php echo (int) $anggota['id']; ?>">
                                            <button type="submit" class="btn-hapus">Hapus</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
$extra_scripts = [$base . 'assets/js/anggota-search.js'];
include __DIR__ . '/../includes/footer.php';
?>