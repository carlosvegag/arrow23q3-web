use PayPal\Api\Plan;
use PayPal\Api\Currency;
use PayPal\Api\ChargeModel;
use PayPal\Api\PaymentDefinition;
use PayPal\Rest\ApiContext;

$apiContext = new ApiContext(new \PayPal\Auth\OAuthTokenCredential(config('services.paypal.client_id'), config('services.paypal.secret')));

$plan = new Plan();
$plan->setName('Plan de Suscripción Mensual')
     ->setDescription('Descripción del plan')
     ->setType('INFINITE');

$paymentDefinition = new PaymentDefinition();
$paymentDefinition->setName('Pago mensual')
                 ->setType('REGULAR')
                 ->frequency('Month')
                 ->frequencyInterval(1)
                 ->amount(new Currency(['value' => 200, 'currency' => 'MXN']));

$plan->setPaymentDefinitions([$paymentDefinition]);
$plan->setMerchantPreferences(['returnUrl' => 'URL de retorno', 'cancelUrl' => 'URL de cancelación']);

try {
    $createdPlan = $plan->create($apiContext);

    // Obtén el ID del plan recién creado
    $planId = $createdPlan->getId();

    // Guarda el $planId en tu base de datos junto con el usuario correspondiente

    return redirect()->to($createdPlan->getApprovalLink());
} catch (Exception $e) {
    // Maneja errores
}