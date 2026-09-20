<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model AlumniKuesionerAutofill
 *
 * Terhubung ke Database View `v_alumni_kuesioner_autofill` yang menyediakan
 * data profil alumni (biodata, akademik, yudisium, pekerjaan, atasan) yang telah dipetakan
 * ke kode instrumen kuesioner (F1 s/d F18C, BIO_*) untuk autofill instan.
 */
class AlumniKuesionerAutofill extends Model
{
    use HasFactory;

    protected $table = 'v_alumni_kuesioner_autofill';

    protected $primaryKey = 'biodata_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'biodata_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function biodata(): BelongsTo
    {
        return $this->belongsTo(Biodata::class, 'biodata_id');
    }
}
