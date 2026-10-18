<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">

    <title>Masuk · {{ config('app.name', 'SIM Klinik Avicena') }}</title>

    @vite(['resources/css/adminlte.css', 'resources/js/adminlte.js'])
</head>

<body class="login-page bg-body-tertiary">
    <div class="login-box">
        <div class="card card-outline card-primary rounded-4 shadow-sm">
            <div class="card-header text-center">
                <span class="h1"><i class="bi bi-heart-pulse"></i></span>
                <span class="d-block h4 fw-light">{{ config('app.name', 'SIM Klinik Avicena') }}</span>
            </div>

            <div class="card-body">
                <p class="login-box-msg">Masuk untuk memulai sesi Anda</p>

                <form method="POST" action="{{ route('login.attempt') }}" novalidate>
                    @csrf

                    @error('username')
                        <div class="alert alert-danger py-2">{{ $message }}</div>
                    @enderror

                    <div class="input-group mb-1">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" value="{{ old('username') }}"
                            class="form-control {{ $errors->has('username') ? 'is-invalid' : '' }}"
                            placeholder="Username" required autofocus autocomplete="username">
                    </div>

                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control"
                            placeholder="Password" required autocomplete="current-password">
                    </div>

                    <div class="input-group mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="ingat" value="1" class="form-check-input" id="ingat">
                            <label class="form-check-label" for="ingat">Ingat saya</label>
                        </div>
                    </div>

                    <div class="input-group mb-3">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-box-arrow-in-right me-1"></i>Masuk</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>
