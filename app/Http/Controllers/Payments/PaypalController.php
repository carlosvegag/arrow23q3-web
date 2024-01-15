<?php

namespace App\Http\Controllers\Payments;
use App\Http\Controllers\Controller as Controller;
use PayPal\Api\PaymentDefinition as PayPalPaymentDefinition;
use PayPal\Api\Plan;
use PayPal\Api\Patch;
use PayPal\Api\PatchRequest;
use PayPal\Api\ChargeModel;
use PayPal\Api\Currency;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\Agreement;
use PayPal\Api\AgreementStateDescriptor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Subscription; 
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Core\PayPalHttpClient;
use PayPalHttp\Environment\SandboxEnvironment;
use PayPalHttp\HttpClient;
use Carbon\Carbon;
use PayPal\Http\Client\SandboxHttpClient;
use PayPal\Common\PayPalModel;
use PayPal\Api\PaymentDefinition;
class PaypalController extends Controller
{
    private $apiContext;

    public function __construct()
    {
        // Configuración del contexto de la API de PayPal
        $credential = new OAuthTokenCredential(
            config('services.paypal.client_id'),
            config('services.paypal.secret')
        );
        $accessToken = $credential->getAccessToken([
            'mode'    => 'sandbox', // o 'live' para producción
            'scope'   => 'required_scope1 required_scope2', // Agrega los scopes necesarios
        ]);
        // Depurar información del token de acceso
        Log::info('Access Token: ' . $accessToken);
        $this->apiContext = new ApiContext($credential);
        // Configuración adicional, incluyendo la verificación SSL y la ruta del certificado
        $this->apiContext->setConfig([
            'mode' => config('services.paypal.mode'),
            'log.LogEnabled' => config('services.paypal.log.enabled'),
            'log.FileName' => storage_path('logs/paypal.log'),
            'log.LogLevel' => config('services.paypal.log.level'),
            'cache.enabled' => config('services.paypal.cache.enabled'),
            'cache.FileName' => storage_path('logs/paypal-cache'),
            'http.CURLOPT_SSL_VERIFYPEER' => false, 
            'http.CURLOPT_SSL_VERIFYHOST' => false,
        ]);
        Log::info('http.CURLOPT_SSL_VERIFYPEER: ' . $this->apiContext->getConfig('http.CURLOPT_SSL_VERIFYPEER'));

    }

    public function createSubscription(Request $request)
    {
        Log::info('Si se accedió a esta ruta');
        $usuario_id = $request->input('usuario_id');
        $validator = Validator::make($request->all(), [
            'usuario_id' => 'required|numeric',
        ]);

        // Validar el método de solicitud
        if ($validator->fails() || !$request->isMethod('post')) {
            Log::error('Validación o método incorrecto. Detalles: ' . print_r($validator->errors(), true));
            // Resto del código...
        }

        // Crear la suscripción
        $paymentDefinition = $this->createSubscriptionPaymentDefinition();
        $plan = new Plan();
        $plan->setName('ARROW - Suscripción del usuario')
            ->setDescription('Suscripción mensual por cada usuario que tenga permitido dar de alta un nuevo reporte de avance')
            ->setType('INFINITE')
            ->setPaymentDefinitions([$paymentDefinition]);

        // Crear el modelo de facturación
        $merchantPreferences = new MerchantPreferences();
        $merchantPreferences->setReturnUrl(route('paypal.execute')) // Ruta para ejecutar el pago después de que el usuario regrese de PayPal
            ->setCancelUrl(route('usuarios.cancelar', ['sub_id' => ':sub_id']))
            ->setAutoBillAmount('yes')
            ->setInitialFailAmountAction('CONTINUE')
            ->setMaxFailAttempts(3);
        $plan->setMerchantPreferences($merchantPreferences);

        // Crear el plan en PayPal
        try {
            $createdPlan = $plan->create($this->apiContext);
            $createdPlan->activate($this->apiContext);

            // Obtener la URL de aprobación del plan
            $approvalUrl = $createdPlan->getApprovalLink();

            // Registrar información en los logs
            Log::info('Plan creado y activado en PayPal. URL de aprobación: ' . $approvalUrl);

            // Redirigir al usuario a la página de éxito de PayPal
            return redirect()->route('paypal.success', ['approval_url' => $approvalUrl]);
        } catch (\Exception $ex) {
            // Registrar información en los logs en caso de error
            Log::error('Error al crear y activar el plan en PayPal: ' . $ex->getTraceAsString());
            return redirect()->route('usuarios.index')->with(['error' => $ex->getMessage()]);
        }
    }

    public function executePayment(Request $request)
    {
        Log::info('ejecutando executePayment');
        try {
            // Obtener el ID del usuario de la URL o de donde lo estés enviando
            $userId = $request->input('user_id');
            // Obtener el token de PayPal para administrar la suscripción
            $paypalPlanId = $request->input('paypal_plan_id');
            // Recuperar la suscripción desde la base de datos
            $subscription = DB::table('subscriptions')->where('user_id', $userId)->first();
            if (!$subscription) {
                return redirect()->route('usuarios.index')->with(['error' => 'Suscripción no encontrada para este usuario']);
            }
            $dateStartedAt = now();
            $dateEndsOn = now()->addDays(30);
            // Subir la suscripción a la base de datos
            DB::table('subscriptions')
                ->where('user_id', $userId)
                ->update([
                    'paypal_plan_id' => $paypalPlanId,
                    'auto_renewal' => 1,
                    'date_started_at' => $dateStartedAt,
                    'date_ends_on' => $dateEndsOn,
                ]);
            // Redirige a la página deseada después de la ejecución del pago
            return redirect()->route('usuarios.index')->with(['message' => 'Pago ejecutado con éxito']);
        } catch (\Exception $ex) {
            return redirect()->route('usuarios.index')->with(['error' => $ex->getMessage()]);
        }
    }
    private function createSubscriptionPaymentDefinition()
    {
        Log::info('ejecutando createSubscriptionPaymentDefinition');
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
        Log::info('ejecutando executeAgreement');
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
        Log::info('ejecutando createPlan');
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
        $subscription = DB::table('subscriptions')
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
            Subscription::where('user_id', $userId)
                ->update([
                    'date_started_at' => $startDate,
                    'date_ends_on' => $nextBillingDate,
                    'auto_renewal' => 1,
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
            Subscription::create([
                'date_started_at' => $startDate,
                'date_ends_on' => $nextBillingDate,
                'auto_renewal' => 1,
                'user_id' => $userId,
            ]);

            return "Nueva suscripción creada con éxito.";
        } catch (\Exception $ex) {
            // Manejar la excepción en caso de algún error durante la creación
            return "Error al crear la suscripción: " . $ex->getMessage();
        }
    } //
    public function cancelSubscription(Request $request, $sub_id)
    {
        dd($sub_id);
        try {
            $clientId = env('PAYPAL_CLIENT_ID');
            $secret = env('PAYPAL_SECRET');
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
                // Realiza la solicitud para cancelar la suscripción
                $cancelUrl = $apiUrl . 'billing/subscriptions/' . $sub_id . '/cancel';
                $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                    ])
                    ->post($cancelUrl);
                // Verifica si la solicitud de cancelación fue exitosa
                if ($response->successful()) {
                    // Actualiza los datos en la base de datos
                    $subscription = Subscription::where('paypal_plan_id', $sub_id)->first();
                    if ($subscription) {
                        $subscription->auto_renewal = 0;
                        $subscription->cancelled_at = now();
                        $subscription->renewed_cancelled_at = now();
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
            } else {
                // Hubo un error al obtener el token de acceso
                return response()->json(['error' => 'Error al obtener el token de acceso'], $response->status());
            }
        } catch (\Exception $ex) {
            // Manejar la excepción en caso de algún error durante el proceso
            return response()->json(['error' => 'Error durante la cancelación de la suscripción: ' . $ex->getMessage()], 500);
        }
    }
    public function renovarSuscripcion(Request $request, $sub_id)
    {
        try {
            // Obtener la suscripción desde la base de datos
            $suscripcion = Subscription::find($sub_id);
            // Verificar si la suscripción existe y está cancelada
            if ($suscripcion && $suscripcion->auto_renewal == 0) {
                // Reactivar la suscripción utilizando la API de PayPal
                $renewedSubscription = $this->reactivarSuscripcion($suscripcion->paypal_plan_id);
                // Actualizar la información en la base de datos
                $suscripcion->auto_renewal = 1;
                $suscripcion->cancelled_at = null;
                $suscripcion->renewed_cancelled_at = null;
                $suscripcion->save();
                // Puedes realizar más acciones según la respuesta de PayPal
                // Redirigir o mostrar un mensaje de éxito
                return redirect()->route('usuarios.index')->with('success', 'Suscripción renovada con éxito.');
            } else {
                // La suscripción no existe o no está cancelada, manejar según tus necesidades
                return redirect()->route('usuarios.index')->with('error', 'La suscripción no está cancelada o no existe.');
            }
        } catch (\Exception $ex) {
            // Manejar la excepción en caso de algún error durante el proceso
            return redirect()->route('usuarios.index')->with('error', 'Error al renovar la suscripción: ' . $ex->getMessage());
        }
    }
    // Método para reactivar la suscripción utilizando la API de PayPal
    private function reactivarSuscripcion($subscriptionId)
    {
        try {
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
        } catch (\Exception $ex) {
            // Manejar la excepción en caso de algún error durante la reactivación
            throw new \Exception('Error al reactivar la suscripción: ' . $ex->getMessage());
        }
    }
}