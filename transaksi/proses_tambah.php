<?php
require __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: tambah.php');
    exit;
}

$anggotaId = filter_input(INPUT_POST, 'anggota_id', FILTER_VALIDATE_INT);
$bukuId = filter_input(INPUT_POST, 'buku_id', FILTER_VALIDATE_INT);

if (!$anggotaId || !$bukuId) {
    $_SESSION['flash'] = ['type' => 'error', 'pesan' => 'Anggota dan buku wajib dipilih.'];
    header('Location: tambah.php');
    exit;
}

require __DIR__ . '/../includes/koneksi.php';

try {
    $pdo->beginTransaction();

    $anggotaStmt = $pdo->prepare('SELECT id FROM anggota WHERE id = :id FOR UPDATE');
    $anggotaStmt->execute(['id' => $anggotaId]);
    if (!$anggotaStmt->fetchColumn()) {
        throw new DomainException('Anggota tidak ditemukan.');
    }

    $kuotaStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM peminjaman
         WHERE anggota_id = :anggota_id AND status = 'dipinjam'"
    );
    $kuotaStmt->execute(['anggota_id' => $anggotaId]);
    if ((int) $kuotaStmt->fetchColumn() >= 3) {
        throw new DomainException('Kuota anggota penuh. Maksimal 3 buku dapat dipinjam bersamaan.');
    }

    $bukuStmt = $pdo->prepare('SELECT stok FROM buku WHERE id = :id FOR UPDATE');
    $bukuStmt->execute(['id' => $bukuId]);
    $buku = $bukuStmt->fetch(PDO::FETCH_ASSOC);
    if (!$buku || (int) $buku['stok'] < 1) {
        throw new DomainException('Buku tidak ditemukan atau stoknya sudah habis.');
    }

    $insert = $pdo->prepare(
        "INSERT INTO peminjaman (buku_id, anggota_id, tanggal_pinjam, tanggal_jatuh_tempo, status)
         VALUES (:buku_id, :anggota_id, CURRENT_DATE, CURRENT_DATE + 7, 'dipinjam')"
    );
    $insert->execute([
        'buku_id' => $bukuId,
        'anggota_id' => $anggotaId,
    ]);

    $update = $pdo->prepare('UPDATE buku SET stok = stok - 1 WHERE id = :id AND stok > 0');
    $update->execute(['id' => $bukuId]);
    if ($update->rowCount() !== 1) {
        throw new DomainException('Stok buku sudah tidak tersedia.');
    }

    $pdo->commit();
} catch (DomainException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash'] = ['type' => 'error', 'pesan' => $e->getMessage()];
    header('Location: tambah.php');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

$_SESSION['flash'] = ['type' => 'success', 'pesan' => 'Peminjaman berhasil dicatat. Stok buku telah diperbarui.'];
header('Location: ../index.php');
exit;
