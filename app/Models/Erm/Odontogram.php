<?php

namespace App\Models\Erm;

use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Odontogram extends ModelDasar
{
    protected $table = 'erm_odontogram';

    protected function permukaan(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value === null ? [] : explode(',', (string) $value),
            set: fn ($value) => is_array($value) ? (empty($value) ? null : implode(',', $value)) : $value,
        );
    }

    public function pemeriksaan(): BelongsTo
    {
        return $this->belongsTo(Pemeriksaan::class, 'pemeriksaan_id');
    }
}
