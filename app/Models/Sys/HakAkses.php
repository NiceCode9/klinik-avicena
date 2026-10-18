<?php

namespace App\Models\Sys;

use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HakAkses extends ModelDasar
{
    protected $table = 'sys_hak_akses';

    protected function casts(): array
    {
        return [
            'bisa_lihat' => 'boolean',
            'bisa_tambah' => 'boolean',
            'bisa_ubah' => 'boolean',
            'bisa_hapus' => 'boolean',
        ];
    }

    public function peran(): BelongsTo
    {
        return $this->belongsTo(Peran::class, 'peran_id');
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }
}
