<?php

namespace App\Models\Master;

use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tindakan extends ModelDasar
{
    use SoftDeletes;

    protected $table = 'mtr_tindakan';

    protected function casts(): array
    {
        return [
            'tarif' => 'decimal:2',
            'aktif' => 'boolean',
        ];
    }

    public function poli(): BelongsTo
    {
        return $this->belongsTo(Poli::class, 'poli_id');
    }

    public function tindakan(): HasMany
    {
        return $this->hasMany(\App\Models\Erm\Tindakan::class, 'tindakan_id');
    }
}
