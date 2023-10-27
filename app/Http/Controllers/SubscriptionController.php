<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Agreement;
use PayPal\Api\Plan;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;

class SubscriptionController extends Controller {
    public function createSubscription(Request $request) {
        // Configura la API de PayPal
        $apiContext = new ApiContext(new OAuthTokenCredential(
            config('services.paypal.client_id'),
            config('services.paypal.secret')
        ));
        // Obtener datos del formulario
        $userData = $request->all(); // Recopilar datos del usuario desde el formulario
        // Crear una suscripción en PayPal
        // Utilizar la SDK de PayPal para enviar una solicitud a la API de PayPal
        // Procesar la respuesta de PayPal
        // Puede implicar redirecciones, manejo de errores, almacenamiento de datos, etc.
        return view('subscription.success'); // Redirigir al usuario a una página de confirmación
    }
    public function showSubscriptionForm()
    {
        return view('usuarios.editar'); 
    }

    public function listSubscriptions()
    {
        // Lógica para listar las suscripciones existentes
    }

}




    