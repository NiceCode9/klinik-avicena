<?php

namespace App\Services\Erm\Exceptions;

use RuntimeException;

class DataTerkunciException extends RuntimeException
{
    public function __construct(string $pesan = 'Data pemeriksaan sudah terkunci dan tidak dapat diubah.')
    {
        parent::__construct($pesan);
    }
}
