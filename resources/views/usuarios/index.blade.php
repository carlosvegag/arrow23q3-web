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
        
        @if (session('mensaje'))
        <div class="alert alert-success" role="alert">
          {{session('mensaje')}}
        </div>
        @endif

        @if (session('mensaje_error'))
        <div class="alert alert-danger" role="alert">
          {{session('mensaje_error')}}
        </div>
        @endif

        <!-- Exportable Table -->
        <div class="row clearfix">
            <div class="col-lg-12 col-md-12 col-sm-12">
                <div class="card">
                    
                    <div class="body table-responsive">

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                            </ul>
                        </div>
                    @endif
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                        <table class="table table-bordered table-striped ">
                            <thead>
                                <tr>
                                    <th>Id</th>
                                    <th>Nombre</th>
                                    <th>Correo</th>
                                    <th>Rol</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($usuarios as $usuario)
                                    <tr>
                                        <td>{{ $usuario->id}}</td>
                                        <td>{{ $usuario->name}}</td>
                                        <td>{{ $usuario->email}}</td>
                                        <td>

                                            {{$usuario->rol}}
                                            {{-- @if(!empty($usuario->getRoleNames()))
                                                @foreach ($usuario->getRoleNames() as $rolName)
                                                <span>{{$rolName}}</span>
                                                    
                                                @endforeach
                                            @endif --}}
                                        </td>
                                        <td class="d-flex justify-content-around">
                                            @can('editar-usuario')
                                            <a  href="{{ route('usuarios.edit', $usuario->id) }}">
                                            <i class="zmdi zmdi-edit text-warning"></i>
                                            </a>
                                            @endcan
                                            @can('borrar-usuario')
                                            {!! Form::open(['method' => 'DELETE','route' => ['usuarios.destroy', $usuario->id], 'style'=>'display:inline']) !!}
                                            <button type="submit" style="cursor: pointer; background: transparent; border:0px;"><i class="material-icons text-danger">delete</i></button>
                                            {!! Form::close() !!}
                                            @endcan

                                            <button style="cursor: pointer; background: #003087; color: #ffffff; border: 0px;" onclick="enviarFormulario('{{ $usuario->id }}')">Suscripción</button>
                                            <!-- Formulario oculto -->
                                            <form id="enviarForm{{ $usuario->id }}" action="{{ route('usuarios.procesar-pago', ['usuario_id' => $usuario->id]) }}" method="post" style="display:none;">
                                                @csrf
                                                <input type="hidden" name="usuario_id" value="{{ $usuario->id }}">
                                            </form>




                                        </td>
                                    
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="pagination justify-content-end">
                            {!! $usuarios->links() !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- #END# Exportable Table -->
    </div>
    <script>
    function enviarFormulario(usuarioId) {
        var form = document.getElementById('enviarForm' + usuarioId);
        form.submit();
    }

</script>

@endsection
