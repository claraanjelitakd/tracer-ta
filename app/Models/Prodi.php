<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Prodi
 *
 * Mengelola data master Program Studi di UKDW.
 */
class Prodi extends Model
{
    use HasFactory;

    protected $table = 'prodi';

    protected $fillable = [
        'kode_prodi',
        'nama_prodi',
    ];

    public function biodata()
    {
        return $this->hasMany(Biodata::class, 'prodi_id');
    }

    public function biodatas()
    {
        return $this->biodata();
    }

    public function questionSections()
    {
        return $this->hasMany(ProdiQuestionSection::class, 'prodi_id')->orderBy('order');
    }

    public function questions()
    {
        return $this->hasMany(ProdiQuestion::class, 'prodi_id')->orderBy('order');
    }
}
