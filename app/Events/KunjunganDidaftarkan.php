<?php

namespace App\Events;

use App\Models\Registrasi\Kunjungan;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KunjunganDidaftarkan implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Kunjungan $kunjungan) {}
}
