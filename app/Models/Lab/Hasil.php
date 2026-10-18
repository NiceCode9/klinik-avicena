<?php

namespace App\Models\Lab;

use App\Models\Master\ParameterLab;
use App\Models\ModelDasar;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hasil extends ModelDasar
{
    protected $table = 'lab_hasil';

    public function permintaanDetail(): BelongsTo
    {
        return $this->belongsTo(PermintaanDetail::class, 'permintaan_detail_id');
    }

    public function parameterLab(): BelongsTo
    {
        return $this->belongsTo(ParameterLab::class, 'parameter_lab_id');
    }

    public function penginput(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diinput_oleh');
    }
}
