<?php

// TuControlador.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function administrarSuscripcion($sub_id)
    {
        // Consultar la suscripción directamente en la base de datos
        $suscripcion = DB::table('subscriptions')->where('idsuscriptions', $sub_id)->first();
        if (is_null($suscripcion)) {
            // Manejar el caso cuando no se encuentra la suscripción
            abort(404); // Puedes personalizar esto según tus necesidades
        }
        // Formatear los datos para la vista
        $datosVista = [
            'precio' => $suscripcion->precio,
            'date_started_at' => $suscripcion->date_started_at,
            'date_ends_on' => $suscripcion->date_ends_on,
            'renewal' => $suscripcion->renewal == 1 ? "SI" : "NO",
        ];

        // Renderizar la vista con los datos
        return view('administrar', $datosVista);
    }
}




    