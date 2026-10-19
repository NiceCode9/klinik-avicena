<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Apotek\MutasiStok;
use App\Models\Apotek\Opname;
use App\Models\Apotek\Penerimaan;
use App\Models\Apotek\PenjualanUmum;
use App\Models\Apotek\ReturPasien;
use App\Models\Apotek\ReturSupplier;
use App\Models\Billing\Pembayaran;
use App\Models\Erm\Resep;
use App\Models\Lab\Hasil;
use App\Models\Lab\Permintaan;
use App\Models\Master\Dokter;
use App\Models\Registrasi\Kunjungan;
use App\Models\Registrasi\Ttv;
use App\Models\Sys\LogAktivitas;
use App\Models\Sys\Peran;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['nama', 'username', 'email', 'password', 'aktif', 'peran_id', 'dokter_id'])]
#[Hidden(['password', 'remember_token'])]
#[Table('sys_pengguna')]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'aktif' => 'boolean',
        ];
    }

    public function peran(): BelongsTo
    {
        return $this->belongsTo(Peran::class, 'peran_id');
    }

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'dokter_id');
    }

    public function logAktivitas(): HasMany
    {
        return $this->hasMany(LogAktivitas::class, 'pengguna_id');
    }

    public function kunjungan(): HasMany
    {
        return $this->hasMany(Kunjungan::class, 'dibuat_oleh');
    }

    public function ttv(): HasMany
    {
        return $this->hasMany(Ttv::class, 'perawat_id');
    }

    public function resep(): HasMany
    {
        return $this->hasMany(Resep::class, 'apoteker_id');
    }

    public function permintaan(): HasMany
    {
        return $this->hasMany(Permintaan::class, 'petugas_id');
    }

    public function hasil(): HasMany
    {
        return $this->hasMany(Hasil::class, 'diinput_oleh');
    }

    public function penerimaan(): HasMany
    {
        return $this->hasMany(Penerimaan::class, 'penerima_id');
    }

    public function mutasiStok(): HasMany
    {
        return $this->hasMany(MutasiStok::class, 'dibuat_oleh');
    }

    public function penjualanUmum(): HasMany
    {
        return $this->hasMany(PenjualanUmum::class, 'petugas_id');
    }

    public function returSupplier(): HasMany
    {
        return $this->hasMany(ReturSupplier::class, 'petugas_id');
    }

    public function returPasien(): HasMany
    {
        return $this->hasMany(ReturPasien::class, 'disetujui_oleh');
    }

    public function opname(): HasMany
    {
        return $this->hasMany(Opname::class, 'apoteker_id');
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'kasir_id');
    }
}
