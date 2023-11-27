<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\usuario;
use App\suscriptions;
class pagarUsuarioController extends Controller
{
    public function procesar(Request $request, User $usuario)
    {
    // Realiza la lógica necesaria utilizando $usuario para acceder al modelo de usuario
        $usuarioid = $request->input('id');
        //logica de la obtención de los datos de la base de datos
        $subscriptions = $usuario->subscriptions;
        //logica de la redirección
        $sub_id = Subscription::where('user_id', $usuarioid)->value('id');
        $renewal = Subscription::where('id', $sub_id)->value('renewal');
        $date_ends_on = Subscription::where('id', $sub_id)->value('date_ends_on');
        $fecha_actual = new DateTime();
        if($fecha_actual < $date_ends_on || $fecha_actual===$date_ends_on){
            if ($renewal == '1' || $renewal == '1') {
                //pagina si la suscripción y la renovación está activa
                return redirect('/administrar-suscripcion/' . $sub_id);
            }else{
                //pagina si la renovación está inactiva, pero la suscripción vigente
                return redirect('/renovar-suscripcion/' . $sub_id);
            }
         
        } else {
            return redirect('/procesar-pago/' . $usuarioid); //pagina si no hay suscripción
        }
    }
    
}
