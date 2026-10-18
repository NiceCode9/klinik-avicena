<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">

    <title>@yield('title', 'Dashboard') · {{ config('app.name', 'Laravel') }}</title>

    {{-- Resolve the color mode before first paint to prevent a theme flash. --}}
    <script>
        (() => {
            'use strict';

            const root = document.documentElement;
            const STORAGE_KEY = 'lte-theme';
            let stored = null;

            try {
                stored = localStorage.getItem(STORAGE_KEY);
            } catch {
                // localStorage may be unavailable (private mode, sandboxed iframe).
            }

            const authored = root.getAttribute('data-bs-theme');
            let resolved = 'light';

            if (stored === 'dark' || stored === 'light') {
                resolved = stored;
            } else if (authored === 'dark' || authored === 'light') {
                resolved = authored;
            } else if (globalThis.matchMedia('(prefers-color-scheme: dark)').matches) {
                resolved = 'dark';
            }

            root.setAttribute('data-bs-theme', resolved);
            root.style.colorScheme = resolved;

            if (resolved !== authored) {
                root.setAttribute('data-lte-theme-resolved', '');
            }
        })();
    </script>

    @vite(['resources/css/adminlte.css', 'resources/js/adminlte.js'])

    @stack('styles')
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        {{-- Header --}}
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button"
                            aria-label="Buka atau tutup sidebar">
                            <i class="bi bi-list"></i>
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav ms-auto">
                    {{-- Fullscreen toggle --}}
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-lte-toggle="fullscreen" aria-label="Mode layar penuh">
                            <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                            <i data-lte-icon="minimize" class="bi bi-fullscreen-exit d-none"></i>
                        </a>
                    </li>

                    {{-- Color mode toggle --}}
                    <li class="nav-item dropdown">
                        <a class="nav-link" href="#" id="bd-theme" aria-label="Ubah mode warna"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-sun-fill" data-lte-theme-icon="light"></i>
                            <i class="bi bi-moon-fill d-none" data-lte-theme-icon="dark"></i>
                            <i class="bi bi-circle-half d-none" data-lte-theme-icon="auto"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="bd-theme"
                            style="--bs-dropdown-min-width: 8rem">
                            <li>
                                <button type="button" class="dropdown-item d-flex align-items-center"
                                    data-bs-theme-value="light" aria-pressed="false">
                                    <i class="bi bi-sun-fill me-2"></i>
                                    Terang
                                    <i class="bi bi-check-lg ms-auto d-none"></i>
                                </button>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item d-flex align-items-center"
                                    data-bs-theme-value="dark" aria-pressed="false">
                                    <i class="bi bi-moon-fill me-2"></i>
                                    Gelap
                                    <i class="bi bi-check-lg ms-auto d-none"></i>
                                </button>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item d-flex align-items-center"
                                    data-bs-theme-value="auto" aria-pressed="false">
                                    <i class="bi bi-circle-half me-2"></i>
                                    Otomatis
                                    <i class="bi bi-check-lg ms-auto d-none"></i>
                                </button>
                            </li>
                        </ul>
                    </li>

                    <!--begin::User Menu Dropdown-->
                    <li class="nav-item dropdown user-menu">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                            <img src="./assets/img/user2-160x160.jpg" class="user-image rounded-circle shadow"
                                alt="Alexander Pierce" />
                            <span class="d-none d-xl-inline">Alexander Pierce</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                            <!--begin::User Image-->
                            <li class="user-header text-bg-primary">
                                <img src="./assets/img/user2-160x160.jpg" class="rounded-circle shadow"
                                    alt="Alexander Pierce" />
                                <p>
                                    Alexander Pierce - Web Developer
                                    <small>Member since Nov. 2023</small>
                                </p>
                            </li>
                            <!--end::User Image-->
                            <!--begin::Menu Body-->
                            <li class="user-body">
                                <!--begin::Row-->
                                <div class="row">
                                    <div class="col-4 text-center">
                                        <a href="#">Followers</a>
                                    </div>
                                    <div class="col-4 text-center">
                                        <a href="#">Sales</a>
                                    </div>
                                    <div class="col-4 text-center">
                                        <a href="#">Friends</a>
                                    </div>
                                </div>
                                <!--end::Row-->
                            </li>
                            <!--end::Menu Body-->
                            <!--begin::Menu Footer-->
                            <li class="user-footer">
                                <a href="#" class="btn btn-outline-secondary">Profile</a>
                                <a href="#" class="btn btn-outline-danger float-end">Sign out</a>
                            </li>
                            <!--end::Menu Footer-->
                        </ul>
                    </li>
                    <!--end::User Menu Dropdown-->
                </ul>
            </div>
        </nav>

        {{-- Sidebar --}}
        {{-- <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
            <div class="sidebar-brand">
                <a href="{{ route('dashboard') }}" class="brand-link">
                    <i class="bi bi-heart-pulse fs-4 me-2"></i>
                    <span class="brand-text fw-light">{{ config('app.name', 'Laravel') }}</span>
                </a>
            </div>

            <div class="sidebar-search" role="search">
                <label for="sidebar-search-input" class="visually-hidden">Cari menu</label>
                <input type="search" id="sidebar-search-input" class="form-control form-control-sm"
                    placeholder="Cari menu…" autocomplete="off" data-lte-toggle="sidebar-search"
                    data-lte-target="#navigation" />
                <p class="fs-7 text-secondary mt-2 mb-0" data-lte-search-empty role="status" hidden>Tidak ada menu
                    yang cocok.</p>
            </div>

            <div class="sidebar-wrapper">
                <nav class="mt-2" aria-label="Navigasi utama">
                    <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" data-accordion="false"
                        id="navigation">
                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}"
                                class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-speedometer2"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>

                        <li class="nav-header">CONTOH</li>

                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon bi bi-folder"></i>
                                <p>
                                    Contoh Menu
                                    <i class="nav-arrow bi bi-chevron-right"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="#" class="nav-link">
                                        <i class="nav-icon bi bi-circle"></i>
                                        <p>Halaman Pertama</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#" class="nav-link">
                                        <i class="nav-icon bi bi-circle"></i>
                                        <p>Halaman Kedua</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside> --}}
        @include('layouts.sidebar')

        {{-- Main content --}}
        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <h1 class="mb-0 fs-3">@yield('page-title', 'Dashboard')</h1>
                        </div>
                        <div class="col-sm-6">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb float-sm-end">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">@yield('page-title', 'Dashboard')</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    @yield('content')
                </div>
            </div>
        </main>

        {{-- Footer --}}
        <footer class="app-footer">
            <div class="float-end d-none d-sm-inline">v1.0</div>
            <strong>Copyright &copy; {{ now()->year }} {{ config('app.name', 'Laravel') }}.</strong>
            All rights reserved.
        </footer>
    </div>

    @stack('scripts')
</body>

</html>
