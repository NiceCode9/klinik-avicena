<?php

namespace App\Models\Sys;

use App\Models\ModelDasar;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAktivitas extends ModelDasar
{
    protected $table = 'sys_log_aktivitas';

    protected function casts(): array
    {
        return [
            'nilai_lama' => 'array',
            'nilai_baru' => 'array',
        ];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengguna_id');
    }
}
