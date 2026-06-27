@extends('layouts.master')

@section('title')
    Login
@endsection

@section('body_class', 'sinemaku-login-shell')

@section('themes_css')
<link rel="stylesheet" media="screen, print" href="{{asset('css/page-login.css')}}">
@endsection

@section('body')
<main class="sinemaku-login-page">
    <section class="sinemaku-login-shell-card">
        <div class="login-visual">
            <div class="login-brand">
                <img src="{{asset('img/sinemaku.png')}}" alt="{{env('APP_NAME','Sinemaku')}}">
                <div>
                    <strong>{{env('APP_NAME','Sinemaku')}}</strong>
                    <span>Backoffice analytics</span>
                </div>
            </div>

            <div class="login-visual-copy">
                <h1>Welcome back to Sinemaku</h1>
                <p>Masuk untuk memantau laporan film, performa kota, bioskop, dan rekap omset dalam satu tempat.</p>
            </div>
        </div>

        <div class="login-form-panel">
            <div class="login-form-inner">
                <h2>Login</h2>
                <p>Gunakan akun backoffice yang sudah terdaftar.</p>

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <div class="login-input-wrap">
                            <i class="fal fa-envelope"></i>
                            <input type="email" id="email" class="form-control @error('email') is-invalid @enderror"
                                name="email" value="{{ old('email') }}" required autocomplete="email"
                                placeholder="nama@email.com">
                        </div>
                        @error('email')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="login-input-wrap">
                            <i class="fal fa-lock"></i>
                            <input type="password" id="password" class="form-control @error('password') is-invalid @enderror"
                                placeholder="Masukkan password" name="password" required autocomplete="current-password">
                        </div>
                        @error('password')
                        <span class="invalid-feedback d-block" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <div class="d-flex align-items-center justify-content-between flex-wrap mb-4">
                        <div class="custom-control custom-checkbox mb-2 mb-sm-0">
                            <input type="checkbox" class="custom-control-input" name="remember" id="rememberme" {{ old('remember') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="rememberme">Remember me</label>
                        </div>

                        @if (Route::has('password.reset'))
                        <a class="login-forgot-link" href="{{ route('password.request') }}">Forgot password?</a>
                        @endif
                    </div>

                    <button type="submit" class="btn btn-login-modern">
                        <i class="fal fa-sign-in"></i>
                        Login now
                    </button>
                </form>
            </div>
        </div>
    </section>
</main>
@endsection
