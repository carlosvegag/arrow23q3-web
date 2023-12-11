@extends('layouts.panel')
@section('styles')
    <!-- JQuery DataTable Css -->
    <link href="{{ asset('plugins/jquery-datatable/dataTables.bootstrap4.min.css')}}" rel="stylesheet">
@endsection
@section('contenido')
    <div class="container-fluid">
        <div class="block-header">
            <h2>Administrar Suscripción del usuario</h2>
            <small class="text-muted">Bienvenido a la aplicación ARROW</small>
            <div>
                @if (session('mensaje_error'))
                <div class="alert alert-danger" role="alert">
                  {{session('mensaje_error')}}
                </div>
                @endif
               
            </div>
        </div>
        <div class="row clearfix"> 
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="card">
                    <div class="header text-center p-2">
                        <h2>Subir Archivo XLSX</h2>
                        <p>Para subir datos en un archivo XLSX debe estar estructurado de la siguiente manera: <br>
                        1-El formato debe ser en XLSX<br>2-Debe estructurarse de la siguiente manera: <br></p>
                        <form action="{{ url('procesar-archivo') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <input type="file" name="archivo" id="archivo" accept=".xlsx" class="btn btn-raised g-bg-blush2" required>
                            <br>
                            <button type="submit" class="btn btn-raised g-bg-blush2">Subir Archivo</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection