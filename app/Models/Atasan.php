<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Atasan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nama',
        'email',
        'telepon',
    ];

    /**
     * Relasi ke User Login
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke Biodata yang dibawahi oleh atasan ini
     */
    public function biodatas()
    {
        return $this->hasMany(Biodata::class);
    }
}
