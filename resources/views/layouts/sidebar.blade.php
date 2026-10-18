{{--
    Sidebar AdminLTE 4 (Bootstrap 5) - dibangun dari menu dinamis berbasis hak akses.
    $pohonMenu disuplai oleh MenuServiceProvider (hanya menu yang boleh dilihat peran login).
    File ini MENGGANTIKAN seluruh <aside class="app-sidebar"> bawaan template; panggil di layout dengan:
        @include('layouts.sidebar')
    Perlu: adminlte.js (treeview/pushmenu) dan Bootstrap Icons sudah dimuat oleh layout.
--}}
@use('Illuminate\Support\Facades\Route')
@use('Illuminate\Support\Str')

@php
    // Menu aktif jika route saat ini termasuk grup route-nya: 'pasien.index' => 'pasien.*', 'dasbor' => 'dasbor'
    $polaAktif = fn(?string $rute) => $rute &&
        request()->routeIs(str_contains($rute, '.') ? Str::beforeLast($rute, '.') . '.*' : $rute);
    // Route yang belum dibuat tidak menyebabkan error, tautannya jadi '#'
    $buatUrl = fn(?string $rute) => $rute && Route::has($rute) ? route($rute) : '#';
@endphp

<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="{{ $buatUrl('dasbor') }}" class="brand-link">
            {{-- <img src="{{ asset('img/logo.png') }}" alt="Logo" class="brand-image opacity-75 shadow"> --}}
            <span class="brand-text fw-light">{{ config('app.name', 'SIM Klinik Avicena') }}</span>
        </a>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false"
                id="navigation">

                @foreach ($pohonMenu as $menu)
                    @if ($menu->anak->isEmpty())
                        {{-- Menu tunggal (mis. Dasbor) --}}
                        <li class="nav-item">
                            <a href="{{ $buatUrl($menu->rute) }}"
                                class="nav-link {{ $polaAktif($menu->rute) ? 'active' : '' }}">
                                <i class="nav-icon bi {{ $menu->ikon ?: 'bi-circle' }}"></i>
                                <p>{{ $menu->nama }}</p>
                            </a>
                        </li>
                    @else
                        {{-- Grup dengan submenu (treeview) --}}
                        @php $terbuka = $menu->anak->contains(fn ($a) => $polaAktif($a->rute)); @endphp
                        <li class="nav-item {{ $terbuka ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ $terbuka ? 'active' : '' }}">
                                <i class="nav-icon bi {{ $menu->ikon ?: 'bi-folder' }}"></i>
                                <p>
                                    {{ $menu->nama }}
                                    <i class="nav-arrow bi bi-chevron-right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @foreach ($menu->anak as $anak)
                                    <li class="nav-item">
                                        <a href="{{ $buatUrl($anak->rute) }}"
                                            class="nav-link {{ $polaAktif($anak->rute) ? 'active' : '' }}">
                                            <i class="nav-icon bi bi-circle"></i>
                                            <p>{{ $anak->nama }}</p>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                @endforeach

            </ul>
        </nav>
    </div>
</aside>
