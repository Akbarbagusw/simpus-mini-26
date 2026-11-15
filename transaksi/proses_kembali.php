<?php
require __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: kembali.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    $_SESSION['flash'] = ['type' => 'error', 'pesan' => 'ID transaksi tidak valid.'];
    header('Location: kembali.php');
    exit;
}

require __DIR__ . '/../includes/koneksi.php';

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT buku_id, status FROM peminjaman WHERE id = :id FOR UPDATE');
    $stmt->execute(['id' => $id]);
    $transaksi = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$transaksi || $transaksi['status'] !== 'dipinjam') {
        throw new DomainException('Transaksi tidak ditemukan atau sudah dikembalikan.');
    }

    $updateTransaksi = $pdo->prepare(
        "UPDATE peminjaman
         SET status = 'dikembalikan', tanggal_kembali = CURRENT_DATE
         WHERE id = :id AND status = 'dipinjam'"
    );
    $updateTransaksi->execute(['id' => $id]);
    if ($updateTransaksi->rowCount() !== 1) {
        throw new DomainException('Transaksi sudah diproses oleh petugas lain.');
    }

    $updateBuku = $pdo->prepare('UPDATE buku SET stok = stok + 1 WHERE id = :id');
    $updateBuku->execute(['id' => $transaksi['buku_id']]);
    if ($updateBuku->rowCount() !== 1) {
        throw new DomainException('Data buku tidak ditemukan; pengembalian dibatalkan.');
    }

    $pdo->commit();
} catch (DomainException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash'] = ['type' => 'error', 'pesan' => $e->getMessage()];
    header('Location: kembali.php');
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

$_SESSION['flash'] = ['type' => 'success', 'pesan' => 'Buku berhasil dikembalikan dan stok telah diperbarui.'];
header('Location: kembali.php');
exit;
