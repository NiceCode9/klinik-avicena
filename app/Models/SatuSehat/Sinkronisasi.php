<?php

namespace App\Models\SatuSehat;

use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sinkronisasi extends ModelDasar
{
    protected $table = 'sts_sinkronisasi';

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'respons' => 'array',
            'terkirim_pada' => 'datetime',
        ];
    }

    public function induk(): BelongsTo
    {
        return $this->belongsTo(self::class, 'bergantung_pada_id');
    }

    public function dependen(): HasMany
    {
        return $this->hasMany(self::class, 'bergantung_pada_id');
    }
}
