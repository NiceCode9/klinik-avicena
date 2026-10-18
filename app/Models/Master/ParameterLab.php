<?php

namespace App\Models\Master;

use App\Models\Lab\Hasil;
use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParameterLab extends ModelDasar
{
    use SoftDeletes;

    protected $table = 'mtr_parameter_lab';

    protected function casts(): array
    {
        return [
            'nilai_rujukan_min' => 'decimal:3',
            'nilai_rujukan_max' => 'decimal:3',
        ];
    }

    public function pemeriksaanLab(): BelongsTo
    {
        return $this->belongsTo(PemeriksaanLab::class, 'pemeriksaan_lab_id');
    }

    public function hasil(): HasMany
    {
        return $this->hasMany(Hasil::class, 'parameter_lab_id');
    }
}
