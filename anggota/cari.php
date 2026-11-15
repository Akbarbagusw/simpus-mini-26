<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../includes/koneksi.php';

header('Content-Type: application/json; charset=utf-8');

$rawKeyword = $_GET['q'] ?? '';
$keyword = is_string($rawKeyword) ? trim($rawKeyword) : '';

if ($keyword === '') {
    $stmt = $pdo->query(
        'SELECT id, no_anggota, nama, no_hp, alamat
         FROM anggota
         ORDER BY id DESC'
    );
} else {
    $searchPattern = '%' . $keyword . '%';
    $stmt = $pdo->prepare(
        'SELECT id, no_anggota, nama, no_hp, alamat
         FROM anggota
         WHERE no_anggota ILIKE :no_anggota OR nama ILIKE :nama
         ORDER BY id DESC'
    );
    $stmt->execute([
        'no_anggota' => $searchPattern,
        'nama' => $searchPattern,
    ]);
}

echo json_encode(
    ['members' => $stmt->fetchAll(PDO::FETCH_ASSOC)],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
