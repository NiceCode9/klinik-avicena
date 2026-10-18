<?php

namespace App\Http\Middleware;

use App\Services\IzinService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PeriksaIzin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $kode, string $aksi = 'lihat'): Response
    {
        abort_unless(IzinService::boleh($request->user(), $kode, $aksi), 403, 'Anda tidak memiliki hak akses.');

        return $next($request);
    }
}
