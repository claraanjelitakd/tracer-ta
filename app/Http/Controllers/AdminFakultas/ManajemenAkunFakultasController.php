<?php

namespace App\Http\Controllers\AdminFakultas;

use App\Http\Controllers\Controller;
use App\Models\DataAkademik;
use App\Models\LogActivity;
use App\Models\Prodi;
use App\Models\RefFakultas;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class ManajemenAkunFakultasController extends Controller
{
    /**
     * Tampilkan Halaman Manajemen Akun Mahasiswa / Alumni Se-Fakultas
     */
    public function index(Request $request): Response
    {
        $adminUser = Auth::user();
        $fakultasId = $adminUser->fakultas_id;
        $search = $request->input('search');
        $prodiFilter = $request->input('prodi_id');

        // Ambil seluruh prodi dalam fakultas ini
        $prodiIdsInFakultas = Prodi::where('fakultas_id', $fakultasId)->pluck('id')->toArray();

        $query = User::with(['prodi', 'biodata.dataAkademik'])
            ->where('role', 'alumni')
            ->whereIn('prodi_id', $prodiIdsInFakultas);

        if ($prodiFilter) {
            $query->where('prodi_id', $prodiFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name', 'asc')->paginate(15)->withQueryString();

        $users->getCollection()->transform(function ($u) {
            $dobFormatted = null;
            if ($u->biodata && $u->biodata->tanggal_lahir) {
                $dobFormatted = Carbon::parse($u->biodata->tanggal_lahir)->format('dmY');
            } elseif ($u->biodata && $u->biodata->dataAkademik && $u->biodata->dataAkademik->tanggal_lahir) {
                $dobFormatted = Carbon::parse($u->biodata->dataAkademik->tanggal_lahir)->format('dmY');
            } else {
                $dataAkad = DataAkademik::where('nim', $u->username)->first();
                if ($dataAkad && $dataAkad->tanggal_lahir) {
                    $dobFormatted = Carbon::parse($dataAkad->tanggal_lahir)->format('dmY');
                }
            }
            $u->default_dob_password = $dobFormatted;

            return $u;
        });

        $currentFakultas = RefFakultas::find($fakultasId);
        $prodisInFakultas = Prodi::where('fakultas_id', $fakultasId)->orderBy('nama_prodi', 'asc')->get();

        return Inertia::render('AdminFakultas/ManajemenAkun/Index', [
            'users' => $users,
            'currentFakultas' => $currentFakultas,
            'prodis' => $prodisInFakultas,
            'filters' => ['search' => $search, 'prodi_id' => $prodiFilter],
            'stats' => [
                'total_alumni' => User::where('role', 'alumni')->whereIn('prodi_id', $prodiIdsInFakultas)->count(),
            ],
        ]);
    }

    /**
     * Perbarui Email / Nama Mahasiswa oleh Admin Fakultas
     */
    public function update(Request $request, $id)
    {
        $adminUser = Auth::user();
        $prodiIdsInFakultas = Prodi::where('fakultas_id', $adminUser->fakultas_id)->pluck('id')->toArray();
        $user = User::whereIn('prodi_id', $prodiIdsInFakultas)->where('role', 'alumni')->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'username' => 'required|string|max:255|unique:users,username,'.$user->id,
        ]);

        $oldValues = $user->only(['name', 'email', 'username']);
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
        ]);

        if ($user->biodata) {
            $user->biodata->update([
                'email_pribadi' => $request->email,
            ]);
        }

        if ($user->biodata?->dataAkademik) {
            $user->biodata->dataAkademik->update([
                'nama' => $request->name,
                'email_pribadi' => $request->email,
            ]);
        }

        // Catat Audit Trail ke tabel log_activities
        LogActivity::record(
            'UPDATE_USER_ACCOUNT',
            "Admin Fakultas memperbarui data mahasiswa {$user->name} (@{$user->username}). Email diatur ke {$request->email}.",
            $user,
            $oldValues,
            $user->only(['name', 'email', 'username'])
        );

        return back()->with('success', "Data mahasiswa/alumni {$user->name} berhasil diperbarui.");
    }

    /**
     * Reset Password ke Tanggal Lahir (ddmmyyyy) oleh Admin Fakultas
     */
    public function resetToDefaultAndNotify(Request $request, $id)
    {
        $adminUser = Auth::user();
        $prodiIdsInFakultas = Prodi::where('fakultas_id', $adminUser->fakultas_id)->pluck('id')->toArray();
        $user = User::whereIn('prodi_id', $prodiIdsInFakultas)->where('role', 'alumni')->findOrFail($id);

        $newPasswordPlain = null;
        if ($user->biodata && $user->biodata->tanggal_lahir) {
            $newPasswordPlain = Carbon::parse($user->biodata->tanggal_lahir)->format('dmY');
        } elseif ($user->biodata && $user->biodata->dataAkademik && $user->biodata->dataAkademik->tanggal_lahir) {
            $newPasswordPlain = Carbon::parse($user->biodata->dataAkademik->tanggal_lahir)->format('dmY');
        } else {
            $dataAkad = DataAkademik::where('nim', $user->username)->first();
            if ($dataAkad && $dataAkad->tanggal_lahir) {
                $newPasswordPlain = Carbon::parse($dataAkad->tanggal_lahir)->format('dmY');
            }
        }

        if (! $newPasswordPlain) {
            $newPasswordPlain = 'Ukdw123456';
        }

        $user->password = Hash::make($newPasswordPlain);
        $user->must_change_password = true;
        $user->save();

        // Catat Audit Trail ke tabel log_activities
        LogActivity::record(
            'RESET_PASSWORD_DOB',
            "Admin Fakultas mereset password mahasiswa {$user->name} (@{$user->username}) ke format tanggal lahir ({$newPasswordPlain}). Notifikasi dikirim ke {$user->email}.",
            $user
        );

        return back()->with('success', "Password untuk {$user->name} berhasil direset ke (Format Tanggal Lahir: {$newPasswordPlain}) dan instruksi telah dikirim ke email terdaftar ({$user->email}).");
    }
}
