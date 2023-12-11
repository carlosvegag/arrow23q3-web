<?php

namespace App\Http\Controllers\Payments;
use App\Http\Controllers\Controller as Controller;
use PayPal\Api\PaymentDefinition as PayPalPaymentDefinition;
use Illuminate\Http\Request;
use PayPal\Api\Plan;
use PayPal\Api\Patch;
use PayPal\Api\PatchRequest;
use PayPal\Api\ChargeModel;
use PayPal\Api\Currency;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\Agreement;
use PayPal\Api\AgreementStateDescriptor;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Models\Subscription; 
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;

class PaypalController extends Controller
{
    private $apiContext;
    public function __construct()
    {
        // Configuración del contexto de la API de PayPal
        $this->apiContext = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential(
                config('services.paypal.client_id'),
                config('services.paypal.secret')
            )
        );

        $this->apiContext->setConfig(config('services.paypal.settings'));
    }

    /*public function createSubscription(Request $request, $usuario_id)
    {
        
        $apiContext = new ApiContext(
            new OAuthTokenCredential(
                env('PAYPAL_CLIENT_ID'),     // Accede al CLIENT_ID desde el archivo .env
                env('PAYPAL_SECRET')  // Accede al CLIENT_SECRET desde el archivo .env
            )
        );

        // Configuración adicional si es necesario
        $apiContext->setConfig([
            'mode' => env('PAYPAL_MODE', 'sandbox'),  // Accede al modo desde el archivo .env, default 'sandbox'
            // Otras configuraciones según tus necesidades
        ]);
        
        // Lógica para crear una suscripción en PayPal
        $plan = $this->createPlan(); // Función para crear un plan de suscripción
        $agreement = new Agreement();
        $agreement->setName('Subscription Agreement')
            ->setDescription('Subscription to my service')
            ->setStartDate(Carbon::now()->addMinutes(5)->toIso8601String());
        $agreement->setPlan($plan);
        // Asegúrate de almacenar el id del acuerdo en tu base de datos para futuras referencias
        $agreement = $agreement->create($this->apiContext);
        // Redireccionar al usuario a la URL de aprobación de PayPal
        return redirect($agreement->getApprovalLink());
    }*/

    public function createSubscription(Request $request)
    {
        // Lógica para crear una definición de pago recurrente para la suscripción de PayPal
        $paymentDefinition = $this->createSubscriptionPaymentDefinition();

        // Crear un plan de PayPal
        $plan = new Plan();
        $plan->setName('ARROW - Suscripción del usuario')
            ->setDescription('Suscripción mensual por cada usuario que tenga permitido dar de alta un nuevo reporte de avance')
            ->setType('INFINITE') 
            ->setPaymentDefinitions([$paymentDefinition]);

        // Crear el modelo de facturación
        $merchantPreferences = new MerchantPreferences();
        $merchantPreferences->setReturnUrl(route('usuarios.index'))
            ->setCancelUrl(route('usuarios.index'))
            ->setAutoBillAmount('yes')
            ->setInitialFailAmountAction('CONTINUE')
            ->setMaxFailAttempts(3);

        $plan->setMerchantPreferences($merchantPreferences);

        // Crear el plan en PayPal
        try {
            $createdPlan = $plan->create($this->apiContext);

            // Activar el plan
            $createdPlan->activate($this->apiContext);

            // Puedes almacenar $createdPlan->getId() en tu base de datos para futuras referencias

            return response()->json(['message' => 'Suscripción creada con éxito']);
        } catch (\Exception $ex) {
            // Manejar el error
            return response()->json(['error' => $ex->getMessage()], 500);
        }
    }

    private function createSubscriptionPaymentDefinition()
    {
        $paymentDefinition = new PayPalPaymentDefinition();
        $paymentDefinition->setName('Regular Payments')
            ->setType('REGULAR')
            ->setFrequency('Month')
            ->setFrequencyInterval(1)
            ->setCycles(0);

        $currency = new Currency();
        $currency->setCurrency('MXN');

        $amount = new Currency(['value' => 200.00, 'currency' => 'MXN']);
        $paymentDefinition->setAmount($amount);

        $chargeModel = new ChargeModel();
        $chargeModel->setType('TAX')
            ->setAmount(new Currency(['value' => 32.00, 'currency' => 'MXN']));

        $paymentDefinition->setChargeModels([$chargeModel]);

        return $paymentDefinition;
    }

    public function executeAgreement(Request $request)
    {
        $token = $request->input('token');
        $agreement = new Agreement();
        
        try {
            $result = $agreement->execute($token, $this->apiContext);
            // Realizar acciones adicionales después de que la suscripción se haya ejecutado correctamente
            $usuarioid = $request->route('usuarioid');
            // Actualizar la base de datos con la información de la suscripción
            $this->updateSubscription($usuarioid);
            return "Suscripción exitosa. ID del acuerdo: " . $result->getId();
        } catch (\Exception $ex) {
            // Manejar cualquier error que pueda ocurrir durante la ejecución del acuerdo
            return "Error al ejecutar la suscripción: " . $ex->getMessage();
        }
    }

    private function createPlan()
    {      
        $plan = new Plan();
        $plan->setName('ARROW - Suscripción del usuario')
            ->setDescription('Suscripción mensual por cada usuario que tenga permitido dar de alta un nuevo reporte de avance')
            ->setType('INFINITE') // Puedes cambiar a 'FIXED' si hay un número fijo de pagos
            ->setPaymentDefinitions([$paymentDefinition]);

        $paymentDefinition = new PaymentDefinition();
        $paymentDefinition->setName('Regular Payments')
            ->setType('REGULAR')
            ->setFrequency('Month')
            ->setFrequencyInterval(1)
            ->setCycles(0)
            ->setAmount(new Currency(['value' => 200, 'currency' => 'MXN']));

        $merchantPreferences = new MerchantPreferences();
        $merchantPreferences->setReturnUrl(url('/execute-agreement'))
            ->setCancelUrl(url('/cancel-agreement'))
            ->setAutoBillAmount('yes')
            ->setInitialFailAmountAction('CONTINUE')
            ->setMaxFailAttempts(0);

        $plan->setPaymentDefinitions([$paymentDefinition]);
        $plan->setMerchantPreferences($merchantPreferences);

        $createdPlan = $plan->create($this->apiContext);

        $patch = new Patch();
        $value = new stdClass();
        $value->state = 'ACTIVE';
        $patch->setOp('replace')
            ->setPath('/')
            ->setValue($value);
        $patchRequest = new PatchRequest();
        $patchRequest->addPatch($patch);
        $createdPlan->update($patchRequest, $this->apiContext);
        return $createdPlan;
    }
    private function updateSubscription($userId)
    {
        $subscription = DB::table('suscriptions')
            ->where('user_id', $userId)
            ->first();

        if ($subscription) {
            // El usuario existe, actualiza el registro
            $this->updateExistingSubscription($userId);
        } else {
            // El usuario no existe, crea un nuevo registro
            $this->createNewSubscription($userId);
        }
    }

    private function updateExistingSubscription($userId)
    {
        try {
            $startDate = now();
            $nextBillingDate = Carbon::parse($startDate)->addMonth(); 
            // Actualizar el registro existente en la base de datos con la información de la suscripción
            DB::table('suscriptions')
                ->where('user_id', $userId)
                ->update([
                    'start_date' => $startDate,
                    'date_ends_on' => $nextBillingDate,
                    'renewal' => 1,
                ]);

            return "Suscripción actualizada con éxito.";
        } catch (\Exception $ex) {
            // Manejar la excepción en caso de algún error durante la actualización
            return "Error al actualizar la suscripción: " . $ex->getMessage();
        }
    }
    private function createNewSubscription($userId)
    {
        try {
            $startDate = now();
            $nextBillingDate = Carbon::parse($startDate)->addMonth(); 
            // Crear un nuevo registro en la base de datos con la información de la suscripción
            DB::table('suscriptions')->insert([
                'start_date' => $startDate,
                'date_ends_on' => $nextBillingDate,
                'renewal' => 1,
                'user_id' => $userId,
            ]);
            return "Nueva suscripción creada con éxito.";
        } catch (\Exception $ex) {
            // Manejar la excepción en caso de algún error durante la creación
            return "Error al crear la suscripción: " . $ex->getMessage();
        }
    }
    public function cancelSubscription(Request $request, $subscriptionId)
    {
        // Obtén las credenciales de PayPal desde el archivo .env
        $clientId = env('PAYPAL_CLIENT_ID');
        $secret = env('PAYPAL_SECRET');
        // Define la URL de la API de PayPal
        $apiUrl = 'https://api-m.sandbox.paypal.com/v1/';

        // Realiza la solicitud de token de acceso
        $response = Http::withBasicAuth($clientId, $secret)
            ->post($apiUrl . 'oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);
        // Verifica si la solicitud fue exitosa
        if ($response->successful()) {
            $accessToken = $response->json('access_token');

            // Realiza la solicitud para cancelar la suscripción
            $cancelUrl = $apiUrl . 'billing/subscriptions/' . $subscriptionId . '/cancel';
            $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ])
                ->post($cancelUrl);
            // Verifica si la solicitud de cancelación fue exitosa
            if ($response->successful()) {
                // Actualiza los datos en la base de datos
                $subscription = Subscription::where('user_id', $request->post('user_id'))
                    ->where('paypal_subscription_id', $subscriptionId)
                    ->first();
    
                if ($subscription) {
                    $subscription->renewal = 0;
                    $subscription->finish_at = now();
                    $subscription->renewed_canceled_at = now();
                    $subscription->save();
                    // La suscripción se ha cancelado correctamente
                    return response()->json(['message' => 'Suscripción cancelada correctamente']);
                } else {
                    // No se encontró la suscripción en la base de datos
                    return response()->json(['error' => 'Suscripción no encontrada en la base de datos'], 404);
                }
            } else {
                // Hubo un error al cancelar la suscripción
                return response()->json(['error' => 'Error al cancelar la suscripción'], $response->status());
            }
        }
    }
    public function renovarSuscripcion(Request $request, $sub_id)
    {
        // Obtener la suscripción desde la base de datos o mediante la API de PayPal
        $suscripcion = Subscription::get($sub_id); 
        // Verificar si la suscripción está cancelada
        if ($suscripcion->status == 'CANCELLED') {
            // Reactivar la suscripción utilizando la API de PayPal
            $renewedSubscription = $this->reactivarSuscripcion($suscripcion->id);

            // Puedes realizar más acciones según la respuesta de PayPal

            // Redirigir o mostrar un mensaje de éxito
            return redirect()->route('usuarios.index')->with('success', 'Suscripción renovada con éxito.');
        } else {
            // La suscripción no está cancelada, manejar según tus necesidades
            return redirect()->route('usuarios.index')->with('error', 'La suscripción no está cancelada.');
        }
    }
    // Método para reactivar la suscripción utilizando la API de PayPal
    private function reactivarSuscripcion($subscriptionId)
    {
        // Implementa la lógica para reactivar la suscripción usando la API de PayPal
        // Utiliza el SDK de PayPal o la librería que estés utilizando
        // Ejemplo con el SDK oficial de PayPal
        $apiContext = new \PayPal\Rest\ApiContext(
            new \PayPal\Auth\OAuthTokenCredential(
                env('PAYPAL_CLIENT_ID'),
                env('PAYPAL_SECRET')
            )
        );
        $subscription = Subscription::get($subscriptionId, $apiContext);
        // Reactivar la suscripción
        $subscription->revise([], $apiContext);
        return $subscription;
    }
}