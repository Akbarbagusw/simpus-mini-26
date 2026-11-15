<?php
require __DIR__ . '/../includes/auth.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash'] = ['type' => 'error', 'pesan' => 'Permintaan ubah buku tidak valid.'];
    header('Location: list.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$postFields = ['judul', 'pengarang', 'tahun', 'isbn', 'stok', 'kategori'];
$data = [];

foreach ($postFields as $field) {
    $value = $_POST[$field] ?? '';
    if (!is_string($value)) {
        $errors[] = 'Data buku tidak valid.';
        $data[$field] = '';
        continue;
    }

    $data[$field] = trim($value);
}

$judul = $data['judul'];
$pengarang = $data['pengarang'];
$tahun = $data['tahun'];
$isbn = $data['isbn'];
$stok = $data['stok'];
$kategori = $data['kategori'];

if ($id === false || $id === null || $id <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'pesan' => 'ID buku tidak valid.'];
    header('Location: list.php');
    exit;
}

if ($judul === '') {
    $errors[] = 'Judul wajib diisi.';
} elseif (strlen($judul) < 2) {
    $errors[] = 'Judul buku minimal harus 2 karakter.';
}

if ($pengarang === '') {
    $errors[] = 'Pengarang wajib diisi.';
} elseif (strlen($pengarang) < 2) {
    $errors[] = 'Nama pengarang minimal harus 2 karakter.';
}

$tahunInt = filter_var($tahun, FILTER_VALIDATE_INT);
if ($tahunInt === false || $tahunInt < 1900 || $tahunInt > 2026) {
    $errors[] = 'Tahun harus di antara 1900-2026.';
}

$stokInt = filter_var($stok, FILTER_VALIDATE_INT);
if ($stokInt === false || $stokInt < 0) {
    $errors[] = 'Stok tidak boleh negatif.';
}

if ($isbn !== '') {
    if (!preg_match('/^[0-9]+(?:-[0-9]+)*$/', $isbn)) {
        $errors[] = 'ISBN hanya boleh berisi angka dan tanda hubung (misal: 978-602-03-8591-4).';
    } else {
        $digitsOnly = str_replace('-', '', $isbn);
        $digitCount = strlen($digitsOnly);
        if ($digitCount !== 10 && $digitCount !== 13) {
            $errors[] = 'ISBN harus memiliki 10 atau 13 digit angka (saat ini terdeteksi ' . $digitCount . ' digit).';
        }
    }
}

$kategoriList = ['Fiksi', 'Non-Fiksi', 'Referensi', 'Teknologi & Komputer', 'Sains'];
if ($kategori !== '' && !in_array($kategori, $kategoriList, true)) {
    $errors[] = 'Kategori buku tidak valid.';
}

if (!empty($errors)) {
    $_SESSION['old_buku'] = $data;
    $_SESSION['flash'] = [
        'type' => 'error',
        'pesan' => implode(' ', $errors)
    ];
    header('Location: ubah.php?id=' . $id);
    exit;
}

unset($_SESSION['old_buku']);

require __DIR__ . '/../includes/koneksi.php';

$stmt = $pdo->prepare(
    "UPDATE buku
     SET judul = :judul,
         pengarang = :pengarang,
         tahun = :tahun,
         isbn = :isbn,
         stok = :stok,
         kategori = :kategori
     WHERE id = :id"
);
$stmt->execute([
    'judul' => $judul,
    'pengarang' => $pengarang,
    'tahun' => $tahunInt,
    'isbn' => $isbn !== '' ? $isbn : null,
    'stok' => $stokInt,
    'kategori' => $kategori !== '' ? $kategori : 'Umum',
    'id' => $id,
]);

if ($stmt->rowCount() === 0) {
    $_SESSION['flash'] = ['type' => 'error', 'pesan' => 'Data buku tidak ditemukan.'];
    header('Location: list.php');
    exit;
}

$_SESSION['flash'] = [
    'type' => 'success',
    'pesan' => "Buku '{$judul}' berhasil diperbarui."
];
header('Location: list.php');
exit;
