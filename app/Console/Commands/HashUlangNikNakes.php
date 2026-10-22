<?php

namespace App\Console\Commands;

use App\Models\Master\Nakes;
use App\Services\NikHasher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('nakes:hash-ulang-nik')]
#[Description('Hitung ulang nik_hash tenaga kesehatan dengan NIK_HMAC_KEY saat ini')]
class HashUlangNikNakes extends Command
{
    public function handle(): int
    {
        $jumlah = 0;

        DB::transaction(function () use (&$jumlah) {
            Nakes::withTrashed()
                ->whereNotNull('nik')
                ->chunkById(100, function ($daftar) use (&$jumlah) {
                    foreach ($daftar as $nakes) {
                        $hash = NikHasher::hash($nakes->nik);

                        if ($hash === $nakes->nik_hash) {
                            continue;
                        }

                        $nakes->forceFill(['nik_hash' => $hash])->saveQuietly();
                        $jumlah++;
                    }
                });
        });

        $this->info("nik_hash diperbarui: {$jumlah} baris.");

        return self::SUCCESS;
    }
}
