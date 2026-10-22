<?php

namespace App\Http\Controllers\SatuSehat;

use App\Http\Controllers\Controller;
use App\Services\SatuSehat\PemetaanService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class PemetaanController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('izin:satusehat.pemetaan,lihat', only: ['index']),
        ];
    }

    public function index(Request $request, PemetaanService $pemetaan): View
    {
        $kategori = $request->query('kategori', 'obat');
        $kategori = is_string($kategori) ? $kategori : 'obat';

        return view('satusehat.pemetaan.index', $pemetaan->belumTerpetakan($kategori));
    }
}
