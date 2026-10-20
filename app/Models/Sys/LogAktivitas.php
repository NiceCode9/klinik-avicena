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

    /**
     * Catat aksi penting (login, logout, ubah hak akses, dst.) tanpa memuat data sensitif.
     *
     * @param  array<string, mixed>|null  $nilaiBaru
     */
    public static function catat(?User $pengguna, string $aksi, ?string $namaTabel = null, ?int $dataId = null, ?array $nilaiBaru = null): void
    {
        static::create([
            'pengguna_id' => $pengguna?->id,
            'aksi' => $aksi,
            'nama_tabel' => $namaTabel,
            'data_id' => $dataId,
            'nilai_baru' => $nilaiBaru,
            'alamat_ip' => request()->ip(),
        ]);
    }
}
