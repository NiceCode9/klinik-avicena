<?php

namespace App\Models\Sys;

use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends ModelDasar
{
    protected $table = 'sys_menu';

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function induk(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'induk_id');
    }

    public function anak(): HasMany
    {
        return $this->hasMany(Menu::class, 'induk_id');
    }

    public function hakAkses(): HasMany
    {
        return $this->hasMany(HakAkses::class, 'menu_id');
    }
}
