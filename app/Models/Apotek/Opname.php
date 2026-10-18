<?php

namespace App\Models\Apotek;

use App\Models\ModelDasar;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opname extends ModelDasar
{
    protected $table = 'apt_opname';

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function apoteker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'apoteker_id');
    }

    public function opnameDetail(): HasMany
    {
        return $this->hasMany(OpnameDetail::class, 'opname_id');
    }
}
