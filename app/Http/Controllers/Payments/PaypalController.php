<?php
use Illuminate\Http\Request;
use PayPal\Api\Plan;
use PayPal\Api\Patch;
use PayPal\Api\PatchRequest;
use PayPal\Api\Agreement;
use PayPal\Api\AgreementStateDescriptor;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class PayPalController extends Controller
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

    public function createSubscription(Request $request)
    {
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
            ->setType('infinite');

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
            $nextBillingDate = Carbon::parse($startDate)->addMonth(); // Puedes ajustar esto según la lógica de tu suscripción

            // Actualizar el registro existente en la base de datos con la información de la suscripción
            DB::table('suscriptions')
                ->where('user_id', $userId)
                ->update([
                    'start_date' => $startDate,
                    'date_ends_on' => $nextBillingDate,
                    'renewal' => 1,
                    // Asegúrate de ajustar según la estructura de tu tabla
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
}