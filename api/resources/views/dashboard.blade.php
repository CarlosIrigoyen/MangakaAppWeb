@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Panel Administrativo</h1>
@stop

@section('content')
    <p>Bienvenido administrador</p>

    <!-- Aquí añades tu contenedor después del mensaje -->
    <div class="container">
        <!-- Puedes agregar aquí el contenido que desees dentro del contenedor -->
        <div class="row">
            <div class="col">
            </div>
        </div>
    </div>
@stop

@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
    <script> console.log("Hi, I'm using the Laravel-AdminLTE package!"); </script>
@stop
