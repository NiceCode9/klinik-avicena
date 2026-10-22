<?php

namespace App\Models\Master;

use App\Models\ModelDasar;
use App\Models\User;
use App\Services\NikHasher;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nakes extends ModelDasar
{
    use SoftDeletes;

    protected $table = 'mtr_nakes';

    /** Label pilihan kolom `jenis` (ENUM di DB). */
    public const JENIS = [
        'perawat' => 'Perawat',
        'apoteker' => 'Apoteker',
        'petugas_lab' => 'Petugas Lab',
        'lainnya' => 'Lainnya',
    ];

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'aktif' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Nakes $nakes) {
            $nakes->nik_hash = NikHasher::hash($nakes->nik);
        });
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }
}
