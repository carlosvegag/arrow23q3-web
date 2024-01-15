<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Subscription;
use DateTime;

class PagarUsuarioController extends Controller
{

    public function procesar(Request $request)
    {
        try {
            $usuarioId = $request->input('usuario_id');
            $usuario = User::find($usuarioId);
            // Verifica si $usuario es null antes de acceder a la propiedad 'id'
            if ($usuario === null) {
                return redirect()->back()->withErrors(['error' => 'Usuario no encontrado. ID: ' . $usuarioId]);
            }
            // Intenta acceder a las suscripciones
            $subscriptions = $usuario->subscriptions;
            if ($subscriptions === null || $subscriptions->isEmpty()) {
                // No hay suscripciones, redirige a la ruta 'usuarios.procesar-pago' con el ID del usuario
                Log::info("Se intenta acceder a return redirect()->route('paypal.procesar-pago', ['usuario_id' => $usuarioId]); ");
                return redirect()->route('paypal.procesar-pago', ['usuario_id' => $usuarioId])->with(['_method' => 'post']);
            }
            $renewal = $subscriptions->first()->auto_renewal;
            $dateEndsOn = $subscriptions->first()->date_ends_on;
            $fechaActual = new DateTime();
            if ($fechaActual <= $dateEndsOn) {
                $subId = $subscriptions->first()->id;
                if ($renewal == 1) {
                    // Suscripción con renovación automática, redirige a la ruta 'usuarios.administrar-suscripcion'
                    return redirect()->route('usuarios.administrar-suscripcion', ['sub_id' => $subId]);
                } else {
                    // Suscripción sin renovación automática, redirige a la ruta 'usuarios.renovar-suscripcion'
                    return redirect()->route('usuarios.renovar-suscripcion', ['sub_id' => $subId]);
                }
            } else {
                // La suscripción ha expirado, redirige a la ruta 'usuarios.procesar-pago'
                return redirect()->route('usuarios.procesar-pago', ['usuario_id' => $usuarioId]);
            }
        } catch (\Exception $e) {
            // Manejo de la excepción
            return redirect()->back()->withErrors(['error' => 'Error al procesar las suscripciones: ' . $e->getMessage()]);
        }
    }

}