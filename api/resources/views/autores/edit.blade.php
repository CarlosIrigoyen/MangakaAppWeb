@extends('adminlte::page')

@section('title', 'Editar Autor')

@section('content_header')
    <h1>Editar Autor</h1>
@stop

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('autores.update', $autor->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="{{ $autor->nombre }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="apellido" class="form-label">Apellido</label>
                        <input type="text" class="form-control" id="apellido" name="apellido" value="{{ $autor->apellido }}" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    <a href="{{ route('autores.index') }}" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
@stop
