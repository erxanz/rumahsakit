<?php

declare(strict_types=1);

/**
 * CLI: php bin/create_admin.php [username] [password] [nama]
 * Membuat akun super_admin baru (idempoten).
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();

use App\Config\Config;
use App\Core\Database;

Config::load(BASE_PATH . '/.env');

$username = $argv[1] ?? 'admin';
$password = $argv[2] ?? 'admin123';
$nama = $argv[3] ?? 'Super Administrator';

$pdo = Database::connection();

$role = $pdo->query("SELECT id_role FROM roles WHERE nama_role = 'super_admin'")->fetchColumn();

if ($role === false) {
    fwrite(STDERR, "Role super_admin tidak ditemukan. Jalankan db_pasien.sql terlebih dahulu.\n");
    exit(1);
}

$exists = $pdo->prepare('SELECT id_pengguna FROM pengguna WHERE username = :u');
$exists->execute(['u' => $username]);

if ($exists->fetchColumn() !== false) {
    echo "Pengguna '{$username}' sudah ada. Tidak ada perubahan.\n";
    exit(0);
}

$pdo->beginTransaction();

try {
    $pdo->prepare(
        'INSERT INTO pengguna (nama_lengkap, username, email, password_hash, status)
         VALUES (:nama, :username, :email, :password, :status)'
    )->execute([
        'nama' => $nama,
        'username' => $username,
        'email' => null,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'status' => 'aktif',
    ]);

    $id = (int) $pdo->lastInsertId();

    $pdo->prepare(
        'INSERT INTO pengguna_roles (id_pengguna, id_role) VALUES (:pengguna, :role)'
    )->execute(['pengguna' => $id, 'role' => (int) $role]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Gagal membuat admin: ' . $e->getMessage() . "\n");
    exit(1);
}

echo "Admin berhasil dibuat:\n";
echo "  username : {$username}\n";
echo "  password : {$password}\n";
echo "  role     : super_admin\n";
