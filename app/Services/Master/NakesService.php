<?php

namespace App\Services\Master;

use App\Models\Master\Nakes;
use Illuminate\Support\Arr;

class NakesService
{
    public function buat(array $data): Nakes
    {
        return Nakes::create($this->atribut($data));
    }

    public function perbarui(Nakes $nakes, array $data): void
    {
        $nakes->update($this->atribut($data));
    }

    public function hapus(Nakes $nakes): void
    {
        $nakes->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function atribut(array $data): array
    {
        return Arr::only($data, [
            'nama',
            'jenis',
            'nik',
            'no_str',
            'ihs_practitioner_id',
            'pengguna_id',
            'aktif',
        ]);
    }
}
