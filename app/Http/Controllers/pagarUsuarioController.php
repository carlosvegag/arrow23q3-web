<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use DateTime;

class pagarUsuarioController extends Controller
{
    public function procesar(Request $request)
    {
        try {
            $usuarioid = $request->input('usuario_id');
            $usuario = User::find($usuarioid);

            // Verifica si $usuario es null antes de acceder a la propiedad 'id'
            if ($usuario === null) {
                return redirect()->back()->withErrors(['error' => 'Usuario no encontrado. ID: ' . $usuarioid]);
            }

            // Intenta acceder a las suscripciones
            $subscriptions = $usuario->subscriptions;

            if ($subscriptions === null || $subscriptions->isEmpty()) {
                return redirect()->route('usuarios.procesar-pago', ['usuario_id' => $usuario->id]);
            }

            $renewal = $subscriptions->first()->renewal;
            $date_ends_on = $subscriptions->first()->date_ends_on;

            $fecha_actual = new DateTime();

            if ($fecha_actual <= $date_ends_on) {
                $sub_id = $subscriptions->first()->id;

                if ($renewal == '1') {
                    return redirect('usuarios/administrar-suscripcion/' . $sub_id);
                } else {
                    return redirect('usuarios/renovar-suscripcion/' . $sub_id);
                }
            } else {
                return redirect('usuarios/procesar-pago/' . $usuarioid);
            }
        } catch (\Exception $e) {
            // Manejo de la excepción
            return redirect()->back()->withErrors(['error' => 'Error al procesar las suscripciones: ' . $e->getMessage()]);
        }
    }
}