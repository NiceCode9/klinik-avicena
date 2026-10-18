<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Model dasar seluruh tabel SIM Klinik Avicena.
 * Kolom created_at, updated_at, deleted_at memakai nama standar Laravel.
 */
abstract class ModelDasar extends Model
{
    protected $guarded = ['id'];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
