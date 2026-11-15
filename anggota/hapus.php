<?php
require __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash'] = ['type' => 'error', 'pesan' => 'Permintaan hapus tidak valid.'];
    header('Location: list.php');
    exit;
}

require __DIR__ . '/../includes/koneksi.php';

$roleStmt = $pdo->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
$roleStmt->execute(['id' => (int) $_SESSION['user_id']]);
$userRole = strtolower(trim((string) $roleStmt->fetchColumn()));

if (!in_array($userRole, ['admin', 'petugas'], true)) {
    $_SESSION['flash'] = ['type' => 'error', 'pesan' => 'Akun ini tidak memiliki izin untuk menghapus anggota.'];
    header('Location: list.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    $_SESSION['flash'] = ['type' => 'error', 'pesan' => 'ID anggota tidak valid.'];
    header('Location: list.php');
    exit;
}

$stmt = $pdo->prepare('DELETE FROM anggota WHERE id = :id');
$stmt->execute(['id' => $id]);

if ($stmt->rowCount() > 0) {
    $_SESSION['flash'] = ['type' => 'success', 'pesan' => 'Anggota berhasil dihapus.'];
} else {
    $_SESSION['flash'] = ['type' => 'error', 'pesan' => 'Anggota tidak ditemukan atau sudah dihapus.'];
}

header('Location: list.php');
exit;
