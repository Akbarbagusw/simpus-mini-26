<?php
$config = require __DIR__ . '/config.php';
$sslmode = $_ENV['DB_SSLMODE'] ?? $_SERVER['DB_SSLMODE'] ?? getenv('DB_SSLMODE') ?: 'require';

try {
    $pdo = new PDO(
        "pgsql:host={$config['db_host']};port={$config['db_port']};dbname={$config['db_name']};sslmode={$sslmode}",
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => true
        ]
    );
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}