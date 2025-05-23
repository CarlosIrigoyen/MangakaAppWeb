@extends('adminlte::auth.auth-page', ['auth_type' => 'login'])

@section('adminlte_css_pre')
    <link rel="stylesheet" href="{{ asset('vendor/icheck-bootstrap/icheck-bootstrap.min.css') }}">
@stop

@section('adminlte_css')
    <style>
        body.login-page {
            background: url('{{ asset('vendor/adminlte/dist/img/MangakaBaka.png') }}') no-repeat center center fixed;
            background-size: cover;
        }

        .login-box {
            background-color: rgba(0, 0, 0, 0.7);
            padding: 20px;
            border-radius: 10px;
            color: white;
        }

        .login-title {
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            color: white;
            margin-bottom: 20px;
            text-shadow: 1px 1px 2px #000;
        }

        .login-title img {
            max-height: 70px;
            margin-bottom: 10px;
        }
    </style>

    {{-- Ajuste extra para que la imagen de fondo ocupe TODO el ancho --}}
    <style>
        body.login-page {
            /* fuerza la imagen al 100% de ancho manteniendo proporción */
            background-size: 100% auto !important;
        }
    </style>
@stop

@php( $login_url           = View::getSection('login_url')           ?? config('adminlte.login_url', 'login') )
@php( $register_url        = View::getSection('register_url')        ?? config('adminlte.register_url', 'register') )
@php( $password_reset_url  = View::getSection('password_reset_url')  ?? config('adminlte.password_reset_url', 'password/reset') )

@if (config('adminlte.use_route_url', false))
    @php( $login_url          = $login_url ? route($login_url) : '' )
    @php( $register_url       = $register_url ? route($register_url) : '' )
    @php( $password_reset_url = $password_reset_url ? route($password_reset_url) : '' )
@else
    @php( $login_url          = $login_url ? url($login_url) : '' )
    @php( $register_url       = $register_url ? url($register_url) : '' )
    @php( $password_reset_url = $password_reset_url ? url($password_reset_url) : '' )
@endif

@section('auth_body')

    <form action="{{ $login_url }}" method="post">
        @csrf

        {{-- Email field --}}
        <div class="input-group mb-3">
            <input type="email"
                   name="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}"
                   placeholder="{{ __('adminlte::adminlte.email') }}"
                   autofocus>
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-envelope {{ config('adminlte.classes_auth_icon', '') }}"></span>
                </div>
            </div>
            @error('email')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>

        {{-- Password field --}}
        <div class="input-group mb-3">
            <input type="password"
                   name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="{{ __('adminlte::adminlte.password') }}">
            <div class="input-group-append">
                <div class="input-group-text">
                    <span class="fas fa-lock {{ config('adminlte.classes_auth_icon', '') }}"></span>
                </div>
            </div>
            @error('password')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>


        <div class="row">
            <div class="col-12 d-flex justify-content-center">
                <button type="submit"
                        class="btn btn-block {{ config('adminlte.classes_auth_btn', 'btn-flat btn-primary') }}">
                    <span class="fas fa-sign-in-alt"></span>
                    {{ __('adminlte::adminlte.sign_in') }}
                </button>
            </div>
        </div>
    </form>
@stop



