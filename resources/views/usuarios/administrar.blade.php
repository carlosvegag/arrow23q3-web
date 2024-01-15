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
        <!-- Exportable Table -->
        <div class="row clearfix"> 
            <!-- Colorful Panel Items With Icon -->
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="card">
                    
                    <div class="header text-center p-2">
                        <ul>
                            <li><strong>Precio: $</strong> {{ $subscription->price }}</li>
                            <li><strong>Fecha de inicio:</strong> {{ $subscription->date_started_at }}</li>
                            <li><strong>Fecha de finalización:</strong> {{ $subscription->date_ends_on }}</li>
                            <li><strong>Renovación:</strong> {{ $subscription->auto_renewal ? 'SI' : 'NO' }}</li>
                        </ul>
                        <form method="post" action="{{ $subscription->auto_renewal ? route('usuarios.cancelar', ['sub_id' => $subscription->id]) : route('renovar-suscripcion', ['sub_id' => $subscription->id]) }}">
                            @csrf
                            <button class="btn btn-raised g-bg-blush2" type="submit">
                                @if($subscription->auto_renewal)
                                    Cancelar Suscripción
                                @else
                                    Reanudar Suscripción
                                @endif
                            </button>
                        </form>
                        <a href="https://www.paypal.com/myaccount/autopay/" class="btn btn-raised g-bg-blush2" target="_blank">Gestionar Pago en PayPal</a>
                    </div>
                    
                </div>
            </div>
        </div>

    </div>
@endsection