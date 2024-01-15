
@extends('layouts.panel')
@section('styles')
    <!-- JQuery DataTable Css -->
    <link href="{{ asset('plugins/jquery-datatable/dataTables.bootstrap4.min.css')}}" rel="stylesheet">
@endsection
@section('contenido')
    <div class="container-fluid">
        <div class="block-header">
            <h2>Usuarios</h2>
            <small class="text-muted">Bienvenido a la aplicación ARROW</small>
            <div>
                @can('crear-usuario')
                <a href="{{ route('usuarios.create') }}" class="btn btn-raised btn-success">Agregar usuario</a>
                @endcan
            </div>
        </div>
        @if(session('success'))
            <p>Suscripción Completada, Aprovación: <a href="{{ session('approval_url') }}"></a></p>
        @else
            <p>Hubo un problema con el pago. Por favor, inténtalo de nuevo más tarde.</p>
        @endif
        <div class="block-header">
            <a href="{{ route('usuarios.index')}}" class="btn btn-raised btn-default waves-effect">Regresar</a>
        </div>
@endsection
<!--
                                    <center>
                                    <a href="{{ route('usuarios.index')}}" class="btn btn-raised btn-default waves-effect">Regresar</a>
                                    </center>
                                -->
                                