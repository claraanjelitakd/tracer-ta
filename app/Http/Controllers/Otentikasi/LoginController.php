<?php

namespace App\Http\Controllers\Otentikasi;

use App\Http\Controllers\Controller;
use App\Models\DataAkademik;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

/**
 * LoginController
 *
 * Fungsi: Menangani proses otentikasi (masuk dan keluar) pengguna ke dalam sistem.
 * Tujuan: Memastikan hanya pengguna yang valid yang dapat mengakses halaman yang dilindungi (dashboard).
 */
class LoginController extends Controller
{
    /**
     * Menampilkan Halaman Login
     */
    public function tampilkanHalamanLogin()
    {
        return Inertia::render('Otentikasi/Login');
    }

    /**
     * Proses Login Pengguna
     */
    public function prosesLogin(Request $request)
    {
        $kredensial = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($kredensial)) {
            $request->session()->regenerate();
            $pengguna = Auth::user();

            if ($pengguna->must_change_password && $pengguna->role !== 'admin_biro3') {
                return redirect()->intended('/change-password');
            }

            return self::arahkanBerdasarkanPeran($pengguna->role);
        }

        // Khusus role Alumni: Dukungan masuk menggunakan kata sandi bawaan (default password) berupa tanggal lahir
        $alumniUser = User::where('username', $kredensial['username'])
            ->where('role', 'alumni')
            ->first();

        if ($alumniUser) {
            $tglLahir = $alumniUser->biodata?->tanggal_lahir
                ?? DataAkademik::where('nim', $alumniUser->username)->value('tanggal_lahir');

            if ($tglLahir) {
                $carbonDate = Carbon::parse($tglLahir);
                $expectedFormat = $carbonDate->format('dmY'); // Format ddmmyyyy, contoh: 15082001
                $cleanInput = preg_replace('/[^0-9]/', '', $kredensial['password']);

                if ($cleanInput === $expectedFormat || $kredensial['password'] === $expectedFormat) {
                    // Update kata sandi ke hash baru dan wajibkan ganti kata sandi
                    $alumniUser->password = Hash::make($kredensial['password']);
                    $alumniUser->must_change_password = true;
                    $alumniUser->save();

                    Auth::login($alumniUser);
                    $request->session()->regenerate();

                    return redirect()->intended('/change-password');
                }
            }
        }

        return back()->withErrors([
            'username' => 'Username atau kata sandi yang Anda masukkan tidak sesuai.',
        ])->onlyInput('username');
    }

    /**
     * Proses Logout Pengguna
     *
     * Menghapus sesi autentikasi pengguna saat ini dari guard web,
     * menginvaliasi session token untuk mencegah session fixation,
     * lalu mengarahkan kembali ke halaman beranda utama (Home / Landing Page).
     *
     * @return RedirectResponse
     */
    public function prosesLogout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Fungsi Bantuan: Mengarahkan pengguna berdasarkan role mereka.
     */
    public static function arahkanBerdasarkanPeran($peran)
    {
        switch ($peran) {
            case 'superadmin':
                return redirect()->intended('/superadmin/dashboard');
            case 'admin_biro3':
                return redirect()->intended('/biro3/dashboard');
            case 'admin_fakultas':
                return redirect()->intended('/fakultas/dashboard');
            case 'admin_prodi':
                return redirect()->intended('/prodi/dashboard');
            case 'alumni':
                return redirect()->intended('/alumni/dashboard');
            default:
                return redirect('/');
        }
    }
}
