<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'username', 'password', 'role', 'prodi_id', 'must_change_password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     * Mengatur tipe data atribut saat diakses atau disimpan.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'must_change_password' => 'boolean', // Pastikan bertipe boolean
            'password' => 'hashed', // Password otomatis di-hash
        ];
    }

    /**
     * Program studi yang dinaungi oleh user (khusus role admin_prodi).
     */
    public function prodi()
    {
        return $this->belongsTo(Prodi::class, 'prodi_id');
    }

    /**
     * Get the biodata record associated with the user.
     */
    public function biodata()
    {
        return $this->hasOne(Biodata::class);
    }

    /**
     * Alias for backward compatibility.
     */
    public function alumni()
    {
        return $this->biodata();
    }

    /**
     * Accessor untuk nama pengguna
     */
    public function getNameAttribute($value)
    {
        if ($this->role === 'alumni' && $this->biodata) {
            return $this->biodata->nama ?? $this->biodata->dataAkademik?->nama ?? $value;
        }

        return $value;
    }

    /**
     * Accessor untuk email pengguna
     */
    public function getEmailAttribute($value)
    {
        if ($this->role === 'alumni' && $this->biodata) {
            return $this->biodata->email_pribadi ?? $this->biodata->dataAkademik?->email_pribadi ?? $value;
        }

        return $value;
    }
}
