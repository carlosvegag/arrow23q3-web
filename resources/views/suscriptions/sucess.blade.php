
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
        Suscripción Completada, obtendrá la confirmación por el correo asociado a PayPal
        <div class="block-header">
            <a href="{{ route('usuarios.index')}}" class="btn btn-raised btn-default waves-effect">Regresar</a>
        </div>
@endsection
<!--
                                    <center>
                                    <a href="{{ route('usuarios.index')}}" class="btn btn-raised btn-default waves-effect">Regresar</a>
                                    </center>
                                -->
                                