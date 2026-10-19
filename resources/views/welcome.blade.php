<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Klinik & Apotek Avicena Farma, Jl. Dr. Sutomo No. 60 dan No. 80, Jombang. Layanan penyakit dalam, saraf, gigi, laboratorium, dan apotek. Senin-Sabtu 06.30-20.00 WIB.">

    <title>{{ config('app.name', 'Klinik & Apotek Avicena Farma') }}</title>

    @vite(['resources/css/adminlte.css', 'resources/js/adminlte.js'])

    {{-- Terpasang di sini, bukan di adminlte.css: hanya halaman publik ini yang memakai foto latar. --}}
    <style>
        .welcome {
            background-image: linear-gradient(100deg, rgba(9, 45, 34, 0.92) 0%, rgba(9, 45, 34, 0.74) 52%, rgba(9, 45, 34, 0.55) 100%),
                url('{{ asset('img/hero-avicena.jpg') }}');
            background-size: cover;
            background-position: 68% center;
        }

        .welcome .info-strip {
            background-color: rgba(7, 38, 29, 0.88);
            border-top: 1px solid rgba(231, 245, 238, 0.18);
        }

        .welcome .info-item i,
        .welcome .site-intro {
            color: #A7D7BE;
        }

        @media (max-width: 575.98px) {
            .welcome {
                /* Di layar sempit teks memenuhi lebar: permukaan kiri-pekat tetap jadi latar mayoritas. */
                background-position: center;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .welcome .btn {
                transition: none;
            }
        }

        .welcome .btn-light {
            transition: filter 0.15s ease;
            color: #0B3D2E;
        }

        .welcome .btn-light:hover,
        .welcome .btn-light:focus {
            filter: brightness(0.94);
            color: #0B3D2E;
        }
    </style>
</head>

<body class="welcome min-vh-100 d-flex flex-column">
    <div class="container d-flex flex-column flex-grow-1 py-4">

        <header class="d-flex align-items-center gap-2 text-white">
            <i class="bi bi-heart-pulse fs-4" aria-hidden="true"></i>
            <span class="fw-light fs-5">Avicena&nbsp;Farma</span>
        </header>

        <main class="row flex-grow-1 align-items-center py-5">
            <div class="col-12 col-md-10 col-lg-7">
                <p class="site-intro mb-2 fw-semibold" style="letter-spacing: .25em; font-size: .85rem;">
                    Klinik &amp; Apotek
                </p>
                <h1 class="display-3 fw-semibold text-white mb-3">Avicena Farma</h1>
                <p class="lead text-white mb-1 fw-medium">Dr. Abdul Ghofir, Sp.PD</p>
                <p class="text-white mb-4" style="opacity: .85;">
                    Layanan penyakit dalam, saraf, gigi, laboratorium, dan apotek di Jombang.
                </p>

                <div class="d-flex flex-wrap gap-3">
                    @auth
                        <a class="btn btn-light btn-lg px-4" href="{{ route('dashboard') }}">Buka Aplikasi</a>
                    @endauth
                    @guest
                        <a class="btn btn-light btn-lg px-4" href="{{ route('login') }}">Masuk ke Sistem</a>
                    @endguest
                    <a class="btn btn-lg px-4 text-white d-flex align-items-center gap-2"
                        style="border: 1px solid rgba(231, 245, 238, 0.55);"
                        href="tel:+62321866509">
                        <i class="bi bi-telephone" aria-hidden="true"></i>Hubungi Kami
                    </a>
                </div>
            </div>
        </main>

        <footer class="info-strip text-white rounded-3 mx-n1">
            <div class="container-fluid">
                <div class="row row-cols-1 row-cols-md-3 g-4 py-4">
                    <div class="col info-item">
                        <div class="d-flex gap-2">
                            <i class="bi bi-geo-alt fs-5" aria-hidden="true"></i>
                            <p class="small mb-0">
                                <span class="d-block fw-semibold">Alamat</span>
                                <span style="opacity: .85;">Jl. Dr. Sutomo No. 60 &amp; No. 80, Jombatan,
                                    Kec. Jombang, Jawa Timur 61419</span>
                            </p>
                        </div>
                    </div>
                    <div class="col info-item">
                        <div class="d-flex gap-2">
                            <i class="bi bi-telephone fs-5" aria-hidden="true"></i>
                            <p class="small mb-0">
                                <span class="d-block fw-semibold">Telepon</span>
                                <span style="opacity: .85;">+62 321 866509<br>+62 821-4335-3791</span>
                            </p>
                        </div>
                    </div>
                    <div class="col info-item">
                        <div class="d-flex gap-2">
                            <i class="bi bi-clock fs-5" aria-hidden="true"></i>
                            <p class="small mb-0">
                                <span class="d-block fw-semibold">Jam Apotek</span>
                                <span style="opacity: .85;">Senin-Sabtu 06.30-20.00 WIB<br>Minggu tutup</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container-fluid px-3 pb-3" style="border-top: 1px solid rgba(231, 245, 238, 0.14);">
                <p class="small mb-0 mt-3" style="opacity: .85;">
                    &copy; {{ date('Y') }} Klinik &amp; Apotek Avicena Farma &middot; Foto oleh
                    <a class="text-white text-decoration-underline" href="https://unsplash.com" target="_blank"
                        rel="noopener">Unsplash</a>
                </p>
            </div>
        </footer>

    </div>
</body>

</html>
