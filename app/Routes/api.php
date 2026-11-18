<?php

declare(strict_types=1);

use App\Controllers\AsuransiController;
use App\Controllers\AuditLogController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\DokterController;
use App\Controllers\JadwalDokterController;
use App\Controllers\KunjunganController;
use App\Controllers\KunjunganTindakanController;
use App\Controllers\PasienController;
use App\Controllers\PembayaranController;
use App\Controllers\PenggunaController;
use App\Controllers\PermissionController;
use App\Controllers\PoliController;
use App\Controllers\RekamMedisController;
use App\Controllers\RoleController;
use App\Controllers\TindakanController;
use App\Core\Router;

/** @var Router $router */

$auth = ['auth'];
$admin = ['auth', 'permission:dashboard.view'];

// ------------------------------------------------------------
// AUTH
// ------------------------------------------------------------
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/login-pasien', [AuthController::class, 'loginPasien']);
$router->post('/api/auth/register-pasien', [AuthController::class, 'registerPasien']);
$router->get('/api/auth/me', [AuthController::class, 'me'], $auth);
$router->post('/api/auth/refresh', [AuthController::class, 'refresh'], $auth);

// ------------------------------------------------------------
// DASHBOARD
// ------------------------------------------------------------
$router->get('/api/dashboard', [DashboardController::class, 'stats'], $admin);

// ------------------------------------------------------------
// MASTER: POLI
// ------------------------------------------------------------
$router->group('/api/poli', function (Router $r): void {
    $r->get('/', [PoliController::class, 'index'], ['auth', 'permission:poli.view']);
    $r->get('/{id}', [PoliController::class, 'show'], ['auth', 'permission:poli.view']);
    $r->post('/', [PoliController::class, 'store'], ['auth', 'permission:poli.manage']);
    $r->put('/{id}', [PoliController::class, 'update'], ['auth', 'permission:poli.manage']);
    $r->patch('/{id}', [PoliController::class, 'update'], ['auth', 'permission:poli.manage']);
    $r->delete('/{id}', [PoliController::class, 'destroy'], ['auth', 'permission:poli.manage']);
});

// ------------------------------------------------------------
// MASTER: ASURANSI
// ------------------------------------------------------------
$router->group('/api/asuransi', function (Router $r): void {
    $r->get('/', [AsuransiController::class, 'index'], ['auth', 'permission:pasien.view']);
    $r->get('/{id}', [AsuransiController::class, 'show'], ['auth', 'permission:pasien.view']);
    $r->post('/', [AsuransiController::class, 'store'], ['auth', 'permission:pasien.create']);
    $r->put('/{id}', [AsuransiController::class, 'update'], ['auth', 'permission:pasien.update']);
    $r->patch('/{id}', [AsuransiController::class, 'update'], ['auth', 'permission:pasien.update']);
    $r->delete('/{id}', [AsuransiController::class, 'destroy'], ['auth', 'permission:pasien.delete']);
});

// ------------------------------------------------------------
// PASIEN
// ------------------------------------------------------------
$router->group('/api/pasien', function (Router $r): void {
    $r->get('/', [PasienController::class, 'index'], ['auth', 'permission:pasien.view']);
    $r->get('/{id}', [PasienController::class, 'show'], ['auth', 'permission:pasien.view']);
    $r->post('/', [PasienController::class, 'store'], ['auth', 'permission:pasien.create']);
    $r->put('/{id}', [PasienController::class, 'update'], ['auth', 'permission:pasien.update']);
    $r->patch('/{id}', [PasienController::class, 'update'], ['auth', 'permission:pasien.update']);
    $r->delete('/{id}', [PasienController::class, 'destroy'], ['auth', 'permission:pasien.delete']);
});

// ------------------------------------------------------------
// DOKTER & JADWAL
// ------------------------------------------------------------
$router->group('/api/dokter', function (Router $r): void {
    $r->get('/', [DokterController::class, 'index'], ['auth', 'permission:dokter.view']);
    $r->get('/{id}', [DokterController::class, 'show'], ['auth', 'permission:dokter.view']);
    $r->post('/', [DokterController::class, 'store'], ['auth', 'permission:dokter.manage']);
    $r->put('/{id}', [DokterController::class, 'update'], ['auth', 'permission:dokter.manage']);
    $r->patch('/{id}', [DokterController::class, 'update'], ['auth', 'permission:dokter.manage']);
    $r->delete('/{id}', [DokterController::class, 'destroy'], ['auth', 'permission:dokter.manage']);
});

$router->group('/api/jadwal-dokter', function (Router $r): void {
    $r->get('/', [JadwalDokterController::class, 'index'], ['auth', 'permission:dokter.view']);
    $r->get('/{id}', [JadwalDokterController::class, 'show'], ['auth', 'permission:dokter.view']);
    $r->post('/', [JadwalDokterController::class, 'store'], ['auth', 'permission:dokter.manage']);
    $r->put('/{id}', [JadwalDokterController::class, 'update'], ['auth', 'permission:dokter.manage']);
    $r->patch('/{id}', [JadwalDokterController::class, 'update'], ['auth', 'permission:dokter.manage']);
    $r->delete('/{id}', [JadwalDokterController::class, 'destroy'], ['auth', 'permission:dokter.manage']);
});

// ------------------------------------------------------------
// KUNJUNGAN
// ------------------------------------------------------------
$router->group('/api/kunjungan', function (Router $r): void {
    $r->get('/', [KunjunganController::class, 'index'], ['auth', 'permission:kunjungan.view']);
    $r->get('/antrean/harian', [KunjunganController::class, 'antrean'], ['auth', 'permission:kunjungan.view']);
    $r->get('/{id}', [KunjunganController::class, 'show'], ['auth', 'permission:kunjungan.view']);
    $r->post('/', [KunjunganController::class, 'store'], ['auth', 'permission:kunjungan.manage']);
    $r->put('/{id}', [KunjunganController::class, 'update'], ['auth', 'permission:kunjungan.manage']);
    $r->patch('/{id}', [KunjunganController::class, 'update'], ['auth', 'permission:kunjungan.manage']);
    $r->delete('/{id}', [KunjunganController::class, 'destroy'], ['auth', 'permission:kunjungan.manage']);
    $r->post('/{id}/verifikasi', [KunjunganController::class, 'verify'], ['auth', 'permission:pendaftaran_online.verify']);
    $r->post('/{id}/batal', [KunjunganController::class, 'cancel'], ['auth', 'permission:pendaftaran_online.cancel']);

    // Rincian tindakan per kunjungan
    $r->get('/{id}/tindakan', [KunjunganTindakanController::class, 'index'], ['auth', 'permission:kunjungan.view']);
    $r->post('/{id}/tindakan', [KunjunganTindakanController::class, 'store'], ['auth', 'permission:kunjungan.manage']);
    $r->delete('/{id}/tindakan/{tindakanId}', [KunjunganTindakanController::class, 'destroy'], ['auth', 'permission:kunjungan.manage']);
});

// ------------------------------------------------------------
// REKAM MEDIS
// ------------------------------------------------------------
$router->group('/api/rekam-medis', function (Router $r): void {
    $r->get('/', [RekamMedisController::class, 'index'], ['auth', 'permission:rekam_medis.view']);
    $r->get('/{id}', [RekamMedisController::class, 'show'], ['auth', 'permission:rekam_medis.view']);
    $r->post('/', [RekamMedisController::class, 'store'], ['auth', 'permission:rekam_medis.manage']);
    $r->put('/{id}', [RekamMedisController::class, 'update'], ['auth', 'permission:rekam_medis.manage']);
    $r->patch('/{id}', [RekamMedisController::class, 'update'], ['auth', 'permission:rekam_medis.manage']);
    $r->delete('/{id}', [RekamMedisController::class, 'destroy'], ['auth', 'permission:rekam_medis.manage']);
});

// ------------------------------------------------------------
// TINDAKAN
// ------------------------------------------------------------
$router->group('/api/tindakan', function (Router $r): void {
    $r->get('/', [TindakanController::class, 'index'], ['auth', 'permission:kunjungan.view']);
    $r->get('/{id}', [TindakanController::class, 'show'], ['auth', 'permission:kunjungan.view']);
    $r->post('/', [TindakanController::class, 'store'], ['auth', 'permission:pembayaran.manage']);
    $r->put('/{id}', [TindakanController::class, 'update'], ['auth', 'permission:pembayaran.manage']);
    $r->patch('/{id}', [TindakanController::class, 'update'], ['auth', 'permission:pembayaran.manage']);
    $r->delete('/{id}', [TindakanController::class, 'destroy'], ['auth', 'permission:pembayaran.manage']);
});

// ------------------------------------------------------------
// PEMBAYARAN
// ------------------------------------------------------------
$router->group('/api/pembayaran', function (Router $r): void {
    $r->get('/', [PembayaranController::class, 'index'], ['auth', 'permission:pembayaran.view']);
    $r->get('/{id}', [PembayaranController::class, 'show'], ['auth', 'permission:pembayaran.view']);
    $r->post('/', [PembayaranController::class, 'store'], ['auth', 'permission:pembayaran.manage']);
    $r->put('/{id}', [PembayaranController::class, 'update'], ['auth', 'permission:pembayaran.manage']);
    $r->patch('/{id}', [PembayaranController::class, 'update'], ['auth', 'permission:pembayaran.manage']);
    $r->delete('/{id}', [PembayaranController::class, 'destroy'], ['auth', 'permission:pembayaran.manage']);
    $r->post('/{id}/bayar', [PembayaranController::class, 'bayar'], ['auth', 'permission:pembayaran.manage']);
});

// ------------------------------------------------------------
// PENGGUNA & RBAC
// ------------------------------------------------------------
$router->group('/api/pengguna', function (Router $r): void {
    $r->get('/', [PenggunaController::class, 'index'], ['auth', 'permission:pengguna.manage']);
    $r->get('/{id}', [PenggunaController::class, 'show'], ['auth', 'permission:pengguna.manage']);
    $r->post('/', [PenggunaController::class, 'store'], ['auth', 'permission:pengguna.manage']);
    $r->put('/{id}', [PenggunaController::class, 'update'], ['auth', 'permission:pengguna.manage']);
    $r->patch('/{id}', [PenggunaController::class, 'update'], ['auth', 'permission:pengguna.manage']);
    $r->delete('/{id}', [PenggunaController::class, 'destroy'], ['auth', 'permission:pengguna.manage']);
});

$router->group('/api/roles', function (Router $r): void {
    $r->get('/', [RoleController::class, 'index'], ['auth', 'permission:rbac.manage']);
    $r->get('/{id}', [RoleController::class, 'show'], ['auth', 'permission:rbac.manage']);
    $r->post('/', [RoleController::class, 'store'], ['auth', 'permission:rbac.manage']);
    $r->put('/{id}', [RoleController::class, 'update'], ['auth', 'permission:rbac.manage']);
    $r->patch('/{id}', [RoleController::class, 'update'], ['auth', 'permission:rbac.manage']);
    $r->delete('/{id}', [RoleController::class, 'destroy'], ['auth', 'permission:rbac.manage']);
});

$router->group('/api/permissions', function (Router $r): void {
    $r->get('/', [PermissionController::class, 'index'], ['auth', 'permission:rbac.manage']);
    $r->get('/{id}', [PermissionController::class, 'show'], ['auth', 'permission:rbac.manage']);
    $r->post('/', [PermissionController::class, 'store'], ['auth', 'permission:rbac.manage']);
    $r->put('/{id}', [PermissionController::class, 'update'], ['auth', 'permission:rbac.manage']);
    $r->patch('/{id}', [PermissionController::class, 'update'], ['auth', 'permission:rbac.manage']);
    $r->delete('/{id}', [PermissionController::class, 'destroy'], ['auth', 'permission:rbac.manage']);
});

// ------------------------------------------------------------
// AUDIT LOG
// ------------------------------------------------------------
$router->group('/api/audit-log', function (Router $r): void {
    $r->get('/', [AuditLogController::class, 'index'], ['auth', 'permission:audit.view']);
    $r->get('/{id}', [AuditLogController::class, 'show'], ['auth', 'permission:audit.view']);
});

// ------------------------------------------------------------
// HEALTH CHECK
// ------------------------------------------------------------
$router->get('/api/health', static function (): \App\Core\Response {
    return \App\Core\Response::ok([
        'status' => 'ok',
        'time' => date('c'),
    ]);
});
