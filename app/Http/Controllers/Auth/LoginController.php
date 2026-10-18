<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Sys\LogAktivitas;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('guest', except: ['logout']),
        ];
    }

    public function form(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $kredensial = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $pengguna = User::where('username', $kredensial['username'])->first();

        if (! $pengguna || ! Hash::check($kredensial['password'], $pengguna->password)) {
            return back()->withErrors(['username' => 'Username atau password salah.'])->onlyInput('username');
        }

        if (! $pengguna->aktif) {
            // Jalan keluar satu-satunya adalah administrator mengaktifkan kembali akun.
            return back()->withErrors(['username' => 'Akun Anda nonaktif. Hubungi administrator.'])->onlyInput('username');
        }

        Auth::login($pengguna, $request->boolean('ingat'));
        $request->session()->regenerate();

        LogAktivitas::create([
            'pengguna_id' => $pengguna->id,
            'aksi' => 'login',
            'alamat_ip' => $request->ip(),
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
