<?php
$page_title = "Daftar Buku";
include __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/koneksi.php';

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$rawKeyword = $_GET['q'] ?? '';
$keyword = is_string($rawKeyword) ? trim($rawKeyword) : '';

$perPage = 10;
$requestedPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = max(1, $requestedPage ?: 1);

if ($keyword !== '') {
    $searchPattern = '%' . $keyword . '%';
    $searchCondition = "judul ILIKE :judul
                        OR pengarang ILIKE :pengarang
                        OR COALESCE(isbn, '') ILIKE :isbn
                        OR COALESCE(kategori, '') ILIKE :kategori";
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM buku WHERE {$searchCondition}");
    $countStmt->execute([
        'judul' => $searchPattern,
        'pengarang' => $searchPattern,
        'isbn' => $searchPattern,
        'kategori' => $searchPattern,
    ]);
    $totalRows = (int) $countStmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT * FROM buku
         WHERE {$searchCondition}
         ORDER BY id DESC
         LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue('judul', $searchPattern, PDO::PARAM_STR);
    $stmt->bindValue('pengarang', $searchPattern, PDO::PARAM_STR);
    $stmt->bindValue('isbn', $searchPattern, PDO::PARAM_STR);
    $stmt->bindValue('kategori', $searchPattern, PDO::PARAM_STR);
} else {
    $totalRows = (int) $pdo->query("SELECT COUNT(*) FROM buku")->fetchColumn();
    $stmt = $pdo->prepare("SELECT * FROM buku ORDER BY id DESC LIMIT :limit OFFSET :offset");
}

$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$daftarBuku = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<section>
    <h2>Daftar Buku</h2>

    <?php if ($flash): ?>
        <p class="flash flash-<?php echo $flash['type']; ?>"><?php echo htmlspecialchars($flash['pesan']); ?></p>
    <?php endif; ?>

    <form id="book-search-form" method="get" action="list.php" data-search-url="cari.php" data-can-delete="<?php echo $sudahLogin ? 'true' : 'false'; ?>" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
        <div class="search-box" style="flex: 1; min-width: 250px; display: flex; gap: 0.5rem; align-items: flex-end;">
            <div class="search-input-wrap">
                <label for="search-input">Cari Judul / Pengarang / ISBN / Kategori</label>
                <input type="search" id="search-input" name="q" placeholder="Ketik judul, pengarang, ISBN, atau kategori..." value="<?php echo htmlspecialchars($keyword); ?>" aria-controls="book-results">
            </div>
            <button type="submit" class="btn btn-primary" style="padding: 0.55rem 1rem;">Cari</button>
        </div>
        <?php if ($sudahLogin): ?>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <a href="tambah.php" class="btn btn-primary">+ Tambah Buku</a>
            </div>
        <?php endif; ?>
    </form>

    <p id="book-search-summary" aria-live="polite" style="margin-bottom: 0.85rem; font-size: 0.95rem; color: #444;<?php echo $keyword === '' ? ' display: none;' : ''; ?>">
        Hasil pencarian buku: <strong id="book-search-term"><?php echo htmlspecialchars($keyword); ?></strong>
        (ditemukan <span id="book-search-count"><?php echo $totalRows; ?></span> buku)
    </p>
    <p id="book-search-status" role="status" aria-live="polite"></p>

    <div class="table-responsive" id="book-results">
        <table>
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Pengarang</th>
                    <th>ISBN</th>
                    <th>Tahun</th>
                    <th>Stok</th>
                    <?php if ($sudahLogin): ?>
                        <th>Ditambahkan</th>
                    <?php endif; ?>
                    <?php if ($sudahLogin): ?>
                        <th>Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody id="book-results-body">
                <?php if (empty($daftarBuku)): ?>
                    <tr>
                        <td colspan="<?php echo $sudahLogin ? '7' : '5'; ?>">Belum ada data buku. Silakan tambah lewat menu "Tambah Buku".</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($daftarBuku as $buku): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($buku['judul']); ?></strong>
                                <?php if (!empty($buku['kategori'])): ?>
                                    <span style="display: block; font-size: 0.8rem; color: #666;"><?php echo htmlspecialchars($buku['kategori']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($buku['pengarang']); ?></td>
                            <td><code><?php echo htmlspecialchars(!empty($buku['isbn']) ? $buku['isbn'] : '-'); ?></code></td>
                            <td><?php echo htmlspecialchars($buku['tahun']); ?></td>
                            <td><?php echo htmlspecialchars($buku['stok']); ?></td>
                            <?php if ($sudahLogin): ?>
                                <td>
                                    <small style="color: #666;">
                                        <?php 
                                        if (!empty($buku['tanggal_ditambahkan'])) {
                                            echo htmlspecialchars(date('d/m/Y H:i', strtotime($buku['tanggal_ditambahkan'])));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </small>
                                </td>
                            <?php endif; ?>
                            <?php if ($sudahLogin): ?>
                                <td>
                                    <div class="book-actions" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: nowrap;">
                                        <a href="ubah.php?id=<?php echo (int) $buku['id']; ?>" class="btn btn-secondary" style="padding: 0.45rem 0.75rem;">Edit</a>
                                        <form class="form-hapus" action="hapus.php" method="post" style="display: inline; margin: 0;">
                                            <input type="hidden" name="id" value="<?php echo (int) $buku['id']; ?>">
                                            <button type="submit" class="btn-hapus">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <nav id="book-pagination" class="pagination" aria-label="Navigasi halaman buku"<?php echo $totalPages <= 1 ? ' hidden' : ''; ?>>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php
            $pageParams = ['page' => $i];
            if ($keyword !== '') {
                $pageParams['q'] = $keyword;
            }
            ?>
            <a href="list.php?<?php echo htmlspecialchars(http_build_query($pageParams)); ?>"
               class="<?php echo $i === $page ? 'active' : ''; ?>"
               <?php echo $i === $page ? 'aria-current="page"' : ''; ?>>
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </nav>
</section>
<?php
$extra_scripts = [$base . 'assets/js/buku-search.js'];
include __DIR__ . '/../includes/footer.php';
?>