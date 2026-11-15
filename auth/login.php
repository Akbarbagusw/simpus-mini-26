<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/remember_me.php';
restore_remembered_login();

if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$page_title = 'Login Petugas';
$flash = $_SESSION['flash'] ?? null;
if (isset($_SESSION['flash'])) {
    unset($_SESSION['flash']);
}
include __DIR__ . '/../includes/header.php';
?>
<section class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <span class="auth-kicker">AREA PETUGAS</span>
            <h2>Login Petugas</h2>
            <p>Silakan masukkan Username & Password anda.</p>
        </div>

        <?php if ($flash): ?>
            <p class="flash flash-<?php echo htmlspecialchars($flash['type'] ?? 'error'); ?>">
                <?php echo htmlspecialchars($flash['pesan'] ?? ''); ?>
            </p>
        <?php endif; ?>

        <form action="proses_login.php" method="POST">
            <p>
                <label for="username">Username :</label>
                <input type="text" id="username" name="username" placeholder="Masukkan username petugas" required autofocus>
            </p>

            <p>
                <label for="password">Password :</label>
                <input type="password" id="password" name="password" placeholder="Masukkan password" required>
            </p>

            <!-- <p class="auth-remember">
                <label for="remember_me">
                    <input type="checkbox" id="remember_me" name="remember_me" value="1">
                    Ingat Saya
                </label>
            </p> -->

            <p class="auth-submit">
                <button type="submit">Masuk ke SIMPUS-Mini</button>
            </p>
        </form>

        <div class="auth-footer">
            <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
            <p class="auth-back">
                <a href="../index.php">&larr; Kembali ke Beranda Tamu</a>
            </p>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
