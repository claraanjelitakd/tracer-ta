<?php

use App\Http\Controllers\AdminBiroTiga\KelolaAlumni\DaftarAlumniController;
use App\Http\Controllers\AdminBiroTiga\KelolaAlumni\DetailAlumniController;
use App\Http\Controllers\AdminBiroTiga\KelolaAlumni\SinkronisasiLinkedinController;
use App\Http\Controllers\AdminProdi\KelolaAlumni\DaftarAlumniProdiController;
use App\Http\Controllers\AdminProdi\KelolaPertanyaan\DaftarPertanyaanProdiController;
use App\Http\Controllers\AdminProdi\KelolaPertanyaan\KelolaOpsiProdiController;
use App\Http\Controllers\AdminProdi\KelolaPertanyaan\KelolaSectionProdiController;
use App\Http\Controllers\AdminProdi\KelolaPertanyaan\SimpanPertanyaanProdiController;
use App\Http\Controllers\Alumni\Dashboard\DashboardController;
use App\Http\Controllers\Alumni\Kuesioner\KuesionerController;
use App\Http\Controllers\Alumni\Kuesioner\KuesionerProdiController;
use App\Http\Controllers\Alumni\Kuesioner\SimpanJawabanController;
use App\Http\Controllers\Alumni\Profil\ProfilController;
use App\Http\Controllers\Alumni\Profil\SimpanProfilController;
use App\Http\Controllers\Otentikasi\LoginController;
use App\Http\Controllers\Otentikasi\UbahKataSandiController;
use App\Http\Controllers\SuperAdmin\KelolaAlumni\DaftarAlumniSuperAdminController;
use App\Http\Controllers\SuperAdmin\KelolaAlumni\DetailAlumniSuperAdminController;
use App\Http\Controllers\SuperAdmin\KelolaPertanyaan\DaftarPertanyaanController;
use App\Http\Controllers\SuperAdmin\KelolaPertanyaan\KelolaOpsiController;
use App\Http\Controllers\SuperAdmin\KelolaPertanyaan\SimpanPertanyaanController;
use App\Http\Controllers\SuperAdmin\KelolaSection\KelolaSectionController;
use App\Http\Controllers\Tamu\BerandaController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// Rute Tamu (Guest)
// =========================================================================
Route::get('/', [BerandaController::class, 'tampilkanBeranda']);

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'tampilkanHalamanLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'prosesLogin']);
});

// =========================================================================
// Rute Otentikasi Sesi (Logout)
// =========================================================================
// Rute logout mendukung method POST & GET agar selalu berhasil mengarahkan pengguna kembali ke Beranda (Home)
Route::match(['get', 'post'], '/logout', [LoginController::class, 'prosesLogout'])->name('logout');

// =========================================================================
// Rute Terotentikasi (Authenticated)
// =========================================================================
Route::middleware('auth')->group(function () {

    // Ganti kata sandi wajib
    Route::get('/change-password', [UbahKataSandiController::class, 'tampilkanUbahKataSandi'])->name('change-password');
    Route::post('/change-password', [UbahKataSandiController::class, 'prosesUbahKataSandi']);

    // Rute yang mengharuskan password bawaan sudah diganti
    Route::middleware('must_change_password')->group(function () {

        // -----------------------------------------------------------------
        // Rute Alumni
        // -----------------------------------------------------------------
        Route::middleware('role:alumni')->group(function () {
            // Dashboard
            Route::get('/alumni/dashboard', [DashboardController::class, 'tampilkanDashboard']);

            // Profil
            Route::get('/alumni/profile', [ProfilController::class, 'tampilkanHalamanProfil'])->name('alumni.profile');
            Route::post('/alumni/profile', [SimpanProfilController::class, 'simpanPerubahanProfil']);
            Route::post('/alumni/company', [SimpanProfilController::class, 'tambahPerusahaanBaru'])->name('alumni.company.store');

            // Kuesioner Umum Tracer Study
            Route::get('/alumni/kuesioner', [KuesionerController::class, 'tampilkanKuesioner']);
            Route::post('/alumni/kuesioner', [SimpanJawabanController::class, 'simpanJawabanKuesioner']);

            // Kuesioner Khusus Program Studi
            Route::get('/alumni/kuesioner-prodi', [KuesionerProdiController::class, 'tampilkanKuesionerProdi'])->name('alumni.kuesioner-prodi');
            Route::post('/alumni/kuesioner-prodi', [KuesionerProdiController::class, 'simpanJawaban'])->name('alumni.kuesioner-prodi.simpan');
        });

        // -----------------------------------------------------------------
        // Rute Admin Biro 3 (Biro Kemahasiswaan, Alumni & Pengembangan Karir)
        // -----------------------------------------------------------------
        Route::middleware('role:admin_biro3')->group(function () {
            // Dashboard Utama Biro 3
            Route::get('/biro3/dashboard', [App\Http\Controllers\AdminBiroTiga\Dashboard\DashboardController::class, 'tampilkanDashboard'])->name('biro3.dashboard');

            // Kelola Data Alumni, Verifikasi, & Sinkronisasi Profil LinkedIn
            Route::get('/biro3/alumni', [DaftarAlumniController::class, 'tampilkanDaftarAlumni'])->name('biro3.alumni.index');
            Route::get('/biro3/alumni/{id}', [DetailAlumniController::class, 'tampilkanDetailAlumni'])->name('biro3.alumni.show');
            Route::post('/biro3/alumni/{id}/sync-linkedin', [SinkronisasiLinkedinController::class, 'sinkronisasiDataLinkedin'])->name('biro3.alumni.sync');
            Route::post('/biro3/alumni/{id}/save-linkedin', [SinkronisasiLinkedinController::class, 'simpanDataLinkedin'])->name('biro3.alumni.save');
        });

        // -----------------------------------------------------------------
        // Rute Admin Prodi (Program Studi)
        // -----------------------------------------------------------------
        Route::middleware('role:admin_prodi')->group(function () {
            // Dashboard Utama Program Studi
            Route::get('/prodi/dashboard', [App\Http\Controllers\AdminProdi\Dashboard\DashboardController::class, 'tampilkanDashboard'])->name('prodi.dashboard');

            // Direktori & Detail Alumni Khusus Program Studi
            Route::get('/prodi/alumni', [DaftarAlumniProdiController::class, 'index'])->name('prodi.alumni.index');
            Route::get('/prodi/alumni/{id}', [DaftarAlumniProdiController::class, 'show'])->name('prodi.alumni.show');

            // Kelola Section Kuesioner Prodi
            Route::get('/prodi/sections', [KelolaSectionProdiController::class, 'index'])->name('prodi.sections.index');
            Route::post('/prodi/sections/reorder', [KelolaSectionProdiController::class, 'reorder'])->name('prodi.sections.reorder');
            Route::post('/prodi/sections', [KelolaSectionProdiController::class, 'store'])->name('prodi.sections.store');
            Route::put('/prodi/sections/{id}', [KelolaSectionProdiController::class, 'update'])->name('prodi.sections.update');
            Route::delete('/prodi/sections/{id}', [KelolaSectionProdiController::class, 'destroy'])->name('prodi.sections.destroy');
            // Alias rute tunggal untuk backward compatibility form modal pertanyaan
            Route::post('/prodi/section', [KelolaSectionProdiController::class, 'store'])->name('prodi.section.store');
            Route::put('/prodi/section/{id}', [KelolaSectionProdiController::class, 'update'])->name('prodi.section.update');
            Route::delete('/prodi/section/{id}', [KelolaSectionProdiController::class, 'destroy'])->name('prodi.section.destroy');

            // Kelola Pertanyaan & Opsi Kuesioner Khusus Prodi
            Route::get('/prodi/pertanyaan', [DaftarPertanyaanProdiController::class, 'index'])->name('prodi.pertanyaan.index');
            Route::post('/prodi/pertanyaan', [SimpanPertanyaanProdiController::class, 'store'])->name('prodi.pertanyaan.store');
            Route::put('/prodi/pertanyaan/{id}', [SimpanPertanyaanProdiController::class, 'update'])->name('prodi.pertanyaan.update');
            Route::delete('/prodi/pertanyaan/{id}', [SimpanPertanyaanProdiController::class, 'destroy'])->name('prodi.pertanyaan.destroy');

            // Kelola Pilihan Opsi Pertanyaan Prodi
            Route::post('/prodi/opsi', [KelolaOpsiProdiController::class, 'store'])->name('prodi.opsi.store');
            Route::put('/prodi/opsi/{id}', [KelolaOpsiProdiController::class, 'update'])->name('prodi.opsi.update');
            Route::delete('/prodi/opsi/{id}', [KelolaOpsiProdiController::class, 'destroy'])->name('prodi.opsi.destroy');
        });

        // -----------------------------------------------------------------
        // Rute Superadmin (Otoritas Tertinggi & Pengaturan Instrumen Kuesioner)
        // -----------------------------------------------------------------
        Route::middleware('role:superadmin')->group(function () {
            // Redirect /superadmin langsung ke dashboard
            Route::redirect('/superadmin', '/superadmin/dashboard');

            // Direktori Mahasiswa/Alumni & Audit Kuesioner Tracer
            Route::get('/superadmin/alumni', [DaftarAlumniSuperAdminController::class, 'index'])->name('superadmin.alumni.index');
            Route::get('/superadmin/alumni/{id}', [DetailAlumniSuperAdminController::class, 'show'])->name('superadmin.alumni.show');
            Route::get('/superadmin/alumni/{id}/export-excel', [DetailAlumniSuperAdminController::class, 'exportExcel'])->name('superadmin.alumni.export-excel');
            Route::post('/superadmin/alumni/{id}/profile', [DetailAlumniSuperAdminController::class, 'updateProfile'])->name('superadmin.alumni.profile.update');

            // Dashboard Utama Superadmin
            Route::get('/superadmin/dashboard', [App\Http\Controllers\SuperAdmin\Dashboard\DashboardController::class, 'tampilkanDashboard'])->name('superadmin.dashboard');

            // Kelola Butir Pertanyaan, Opsi Jawaban, & Alur Branching Kuesioner (Modular Controllers)
            Route::get('/superadmin/pertanyaan', [DaftarPertanyaanController::class, 'index'])->name('superadmin.pertanyaan.index');
            Route::post('/superadmin/pertanyaan', [SimpanPertanyaanController::class, 'store'])->name('superadmin.pertanyaan.store');
            Route::post('/superadmin/pertanyaan/reorder', [SimpanPertanyaanController::class, 'reorder'])->name('superadmin.pertanyaan.reorder');
            Route::put('/superadmin/pertanyaan/{id}', [SimpanPertanyaanController::class, 'update'])->name('superadmin.pertanyaan.update');
            Route::delete('/superadmin/pertanyaan/{id}', [SimpanPertanyaanController::class, 'destroy'])->name('superadmin.pertanyaan.destroy');
            Route::post('/superadmin/pertanyaan/{questionId}/options', [KelolaOpsiController::class, 'storeOption'])->name('superadmin.pertanyaan.options.store');
            Route::put('/superadmin/pertanyaan/options/{optionId}', [KelolaOpsiController::class, 'updateOption'])->name('superadmin.pertanyaan.options.update');
            Route::delete('/superadmin/pertanyaan/options/{optionId}', [KelolaOpsiController::class, 'destroyOption'])->name('superadmin.pertanyaan.options.destroy');

            // Kelola Bagian Kuesioner / Section (CRUD & Reorder)
            Route::get('/superadmin/sections', [KelolaSectionController::class, 'index'])->name('superadmin.sections.index');
            Route::post('/superadmin/sections', [KelolaSectionController::class, 'store'])->name('superadmin.sections.store');
            Route::post('/superadmin/sections/reorder', [KelolaSectionController::class, 'reorder'])->name('superadmin.sections.reorder');
            Route::put('/superadmin/sections/{id}', [KelolaSectionController::class, 'update'])->name('superadmin.sections.update');
            Route::delete('/superadmin/sections/{id}', [KelolaSectionController::class, 'destroy'])->name('superadmin.sections.destroy');
        });

    });
});
