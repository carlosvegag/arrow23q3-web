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
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
                    <div class="header p-2">
                        <h2>Subir Archivo XLSX</h2>
                        <p>Para subir datos en un archivo XLSX debe estar estructurado de la siguiente manera: <br>
                        1-El formato debe ser en XLSX o CSV<br>2-Debe estructurarse de la siguiente manera: <br></p>
                        <ol>
                            <li>
                                <h2>Nombrar las Hojas:</h2>
                                <p>Cada conjunto de datos debe estar en una hoja separada dentro de la hoja de cálculo. Asigna un nombre significativo a cada hoja, como "afianzadoras", "usuarios", "clientes", etc.</p>
                            </li>
                            <li>
                                <h2>Columnas y Datos:</h2>
                                <p>Asegúrate de que las columnas en cada hoja coincidan exactamente con las siguientes estructuras:</p>
                                <ul>
                                    <li><strong>Afianzadoras:</strong> nombre, rfc, razon_social, domicilio, telefono</li>
                                    <li><strong>Clientes:</strong> nombre, telefono, email</li>
                                    <li><strong>Contratos:</strong> contrato, nombre_obra, descripcion, fecha_alta, ubicacion, fecha_inicio, fecha_termino, plazo_dias, importe, amortizacion, estatus, id_cliente, id_empresa, id_responsable, id_asistente</li>
                                    <li><strong>Unidades:</strong> nombre, descripcion</li>
                                    <li><strong>Cargos:</strong> nombre, descripcion</li>
                                </ul>
                            </li>
                            <li>
                                <h2>Formato de Fecha:</h2>
                                <p>Para las columnas que contienen fechas (fecha_alta, fecha_inicio, fecha_termino), utiliza el formato "YYYY-MM-DD" para garantizar una interpretación precisa.</p>
                            </li>
                            <li>
                                <h2>Formato de Números:</h2>
                                <p>Asegúrate de que las columnas numéricas (plazo_dias, importe, amortizacion) utilicen el formato numérico adecuado.</p>
                            </li>
                            <li>
                                <h2>Guardar el Archivo:</h2>
                                <p>Guarda el archivo en formato CSV o XLSX según tu preferencia.</p>
                            </li>
                            <li>
                                <h2>Cargar el Archivo:</h2>
                                <p>Utiliza el botón de carga correspondiente en la plataforma para seleccionar y cargar tu archivo preparado.</p>
                            </li>
                        </ol>
                        <form action="{{ route('procesar.archivo') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <input type="file" name="archivo" id="archivo" class="btn btn-raised g-bg-blush2" required>
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