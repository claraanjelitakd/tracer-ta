<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Yudisium extends Model
{
    use HasFactory;

    protected $table = 'yudisiums';

    protected $guarded = ['id'];

    public function dataAkademik()
    {
        return $this->belongsTo(DataAkademik::class, 'nim', 'nim');
    }

    public function biodata()
    {
        return $this->hasOne(Biodata::class, 'nim', 'nim');
    }
}
