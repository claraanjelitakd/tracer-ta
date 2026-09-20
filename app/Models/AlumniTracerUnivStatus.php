<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model AlumniTracerUnivStatus
 *
 * Terhubung ke Database View `v_alumni_tracer_univ_status` yang mengagregasikan
 * jumlah pertanyaan wajib universitas yang sudah dijawab vs total pertanyaan wajib per alumni.
 */
class AlumniTracerUnivStatus extends Model
{
    use HasFactory;

    protected $table = 'v_alumni_tracer_univ_status';

    protected $primaryKey = 'biodata_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'biodata_id' => 'integer',
            'total_mandatory_univ' => 'integer',
            'answered_mandatory_univ' => 'integer',
            'total_answered_univ' => 'integer',
        ];
    }

    public function biodata(): BelongsTo
    {
        return $this->belongsTo(Biodata::class, 'biodata_id');
    }
}
