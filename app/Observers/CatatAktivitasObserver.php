<?php

namespace App\Observers;

use App\Models\Sys\LogAktivitas;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Mencatat CRUD master dan data sistem ke sys_log_aktivitas.
 * Nilai kolom tidak pernah disimpan, hanya nama kolom yang berubah.
 */
class CatatAktivitasObserver
{
    public function created(Model $model): void
    {
        $this->catat($model, 'tambah');
    }

    public function updated(Model $model): void
    {
        $kolom = array_values(array_diff(array_keys($model->getChanges()), ['updated_at']));

        $this->catat($model, 'ubah', $kolom === [] ? null : ['kolom_berubah' => $kolom]);
    }

    public function deleted(Model $model): void
    {
        $this->catat($model, 'hapus');
    }

    /**
     * @param  array<string, mixed>|null  $nilaiBaru
     */
    private function catat(Model $model, string $aksi, ?array $nilaiBaru = null): void
    {
        $pengguna = auth()->user();

        LogAktivitas::catat(
            $pengguna instanceof User ? $pengguna : null,
            $aksi,
            $model->getTable(),
            (int) $model->getKey(),
            $nilaiBaru,
        );
    }
}
