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
                            <li><strong>Preparar el Archivo:</strong>
                                <ul>
                                    <li>Asegúrate de tener un archivo CSV o XLSX preparado con los datos que deseas importar.</li>
                                    <li>Verifica que las columnas del archivo coincidan con la estructura que se espera para el modelo <code>Avance</code>.</li>
                                </ul>
                            </li>
                            <li><strong>Acceder a la Página de Importación:</strong>
                                <ul>
                                    <li>Inicia sesión en la aplicación.</li>
                                    <li>Navega a la página o sección dedicada a la importación de datos. Esto podría ser una sección dentro del panel de administración o una página específica destinada a la importación.</li>
                                </ul>
                            </li>
                            <li><strong>Seleccionar el Archivo:</strong>
                                <ul>
                                    <li>Dentro de la página de importación, deberías encontrar una opción para seleccionar el archivo. Busca un botón o un área que te permita cargar tu archivo CSV o XLSX.</li>
                                </ul>
                            </li>
                            <li><strong>Cargar el Archivo:</strong>
                                <ul>
                                    <li>Haz clic en el botón de carga y selecciona el archivo preparado desde tu dispositivo.</li>
                                </ul>
                            </li>
                            <li><strong>Esperar el Procesamiento:</strong>
                                <ul>
                                    <li>Después de cargar el archivo, la aplicación puede llevar a cabo el procesamiento de los datos. Este proceso puede llevar algún tiempo dependiendo de la cantidad de datos que estás importando.</li>
                                </ul>
                            </li>
                        </ol>
                        <form action="{{ url('Avances/importar-avances') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <input type="file" name="file" id="file" class="btn btn-raised g-bg-blush2" required>
                            <input type="hidden" name="avance_id" value="{{ $avancef->id }}">
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
@if (isset($scripts))
public function showImportView($id)
{
    // Puedes hacer algo con $id si es necesario antes de mostrar la vista
    return view('importaravance', ['id' => $id]);
}
@endif