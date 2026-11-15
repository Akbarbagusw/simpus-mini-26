<?php
require __DIR__ . '/../includes/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

$rawKeyword = $_GET['q'] ?? '';
$keyword = is_string($rawKeyword) ? trim($rawKeyword) : '';
$requestedPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = max(1, $requestedPage ?: 1);
$perPage = 10;

$searchPattern = '%' . $keyword . '%';
$where = "judul ILIKE :judul
          OR pengarang ILIKE :pengarang
          OR COALESCE(isbn, '') ILIKE :isbn
          OR COALESCE(kategori, '') ILIKE :kategori";

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM buku WHERE {$where}");
$countStmt->execute([
    'judul' => $searchPattern,
    'pengarang' => $searchPattern,
    'isbn' => $searchPattern,
    'kategori' => $searchPattern,
]);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);

$stmt = $pdo->prepare(
    "SELECT id, judul, pengarang, isbn, tahun, stok, kategori, tanggal_ditambahkan
     FROM buku
     WHERE {$where}
     ORDER BY id DESC
     LIMIT :limit OFFSET :offset"
);
$stmt->bindValue('judul', $searchPattern, PDO::PARAM_STR);
$stmt->bindValue('pengarang', $searchPattern, PDO::PARAM_STR);
$stmt->bindValue('isbn', $searchPattern, PDO::PARAM_STR);
$stmt->bindValue('kategori', $searchPattern, PDO::PARAM_STR);
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$stmt->execute();

$books = array_map(static function (array $book): array {
    $book['tanggal_ditambahkan'] = !empty($book['tanggal_ditambahkan'])
        ? date('d/m/Y H:i', strtotime($book['tanggal_ditambahkan']))
        : '-';

    return $book;
}, $stmt->fetchAll(PDO::FETCH_ASSOC));

echo json_encode([
    'books' => $books,
    'totalRows' => $totalRows,
    'totalPages' => $totalPages,
    'page' => $page,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
