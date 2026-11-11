<?php

namespace App\Models\Billing;

use App\Models\ModelDasar;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends ModelDasar
{
    protected $table = 'bil_shift';

    protected function casts(): array
    {
        return [
            'dibuka_pada' => 'datetime',
            'ditutup_pada' => 'datetime',
        ];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }

    public function ditutupOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditutup_oleh');
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'shift_id');
    }

    public function setoranDokter(): HasMany
    {
        return $this->hasMany(SetoranDokter::class, 'shift_id');
    }
}
