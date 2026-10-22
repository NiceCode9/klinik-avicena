<?php

namespace App\Services;

use RuntimeException;

class NikHasher
{
    public static function hash(?string $nik): ?string
    {
        $nik = trim((string) $nik);

        if ($nik === '') {
            return null;
        }

        $kunci = trim((string) config('app.nik_hmac_key'));

        if ($kunci === '') {
            throw new RuntimeException('NIK_HMAC_KEY belum diatur.');
        }

        return hash_hmac('sha256', $nik, $kunci);
    }
}
