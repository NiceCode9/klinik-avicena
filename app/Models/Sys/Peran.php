<?php

namespace App\Models\Sys;

use App\Models\ModelDasar;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Peran extends ModelDasar
{
    protected $table = 'sys_peran';

    public function pengguna(): HasMany
    {
        return $this->hasMany(User::class, 'peran_id');
    }

    public function hakAkses(): HasMany
    {
        return $this->hasMany(HakAkses::class, 'peran_id');
    }
}
