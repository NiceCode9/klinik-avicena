<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UbahKataSandiRequest;
use App\Models\Sys\LogAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class KataSandiController extends Controller
{
    public function edit(): View
    {
        return view('auth.kata_sandi');
    }

    public function update(UbahKataSandiRequest $request): RedirectResponse
    {
        $pengguna = $request->user();

        // Cast `hashed` pada model User meng-hash nilai polos secara otomatis.
        $pengguna->update(['password' => $request->validated('kata_sandi')]);

        LogAktivitas::catat($pengguna, 'ubah', 'sys_pengguna', $pengguna->id);

        return back()->with('sukses', 'Kata sandi berhasil diperbarui.');
    }
}
