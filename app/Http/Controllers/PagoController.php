<?php

namespace App\Http\Controllers;

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Payer;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Payment;
use PayPal\Api\Plan;
use PayPal\Api\Currency;
use PayPal\Api\ChargeModel;
use PayPal\Api\PaymentDefinition;

$apiContext = new ApiContext(
    new OAuthTokenCredential(config('services.paypal.client_id'), config('services.paypal.secret'))
);

class PagoController extends Controller { 
    const PREMIUM_PRODUCT_ID = 'I-0VBW9JK2U01S'; //PRODUCT ID Obtenido de la API de PayPal
    private $payPalClient;
    public function __construct(PayPalClient $payPalClient){
        $this->payPalClient=$payPalClient;
    }
    public function index(){
        return $this->payPalClient->getPlans();
    }
    public function createPremiumProduct() {
        $product=[  //Datos de la suscripción
            'name'=> 'ARROW - Suscripcion por usuario',
            'description'=> 'Acceso al usuario seleccionado para dar de alta nuevos reportes de avance',
            'type'=> 'SERVICE',
            'category'=> 'SOFTWARE'
        ];
        try{
            return $this->payPalClient->createProduct($product);
        }catch(ClientException $exception){
            dd($exception->getResponse()->getBody()->getContents());
        }
    }
    public function createProduct($body){
        $response = $this-client->request('POST', '/v1/catalogs/products',[
            'json' => $body,
            'headers' => $this->getHeaders()
        ]);
        return json_decode($response->getBody(), true);
    }
    public function getHeaders(){
        return [
            'Accept' => 'application.json',
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-type' => 'application/json'
        ];
    }
    public function getPlans(){
        //
    }
    public function createMonthlyPremium(){
        $id=self::PREMIUM_PRODUCT_ID;
        $name='Suscripción Mensual';
        $description='Plan mensual de suscripción.';
        $frequency='MONTH';
        $price=200;
        $planBody=$this->buildPlanBody($id, $name, $description, $frequency, $price);
    }
    private function buildPlanBody($planid, $planName, $planDescription, $frequencyUnit, $planPrice){
        $fixedPrice=[
            'value' => $planPrice,
            'currency_code' => 'MXN'
        ];
        $billingCycle = [
            'frequency' => [
                'interval_unit' => '$frequencyUnit',
                'interval_count' => 1
            ],
            'tenure_type' => 'REGULAR',
            'sequence' => 1,
            'total_cycles' => 0,
            'pricing_scheme' => [
                'fixed_price' => $fixedPrice
            ]
        ];
        $paymentPreferences = [];
        $taxes = [];
        return [
            'product_id' => $planid,
            'name' => $planName,
            'description' => $planDescription,
            'status' => 'ACTIVE',
            'biling_cycles' => [
                $billingCycle
            ],
            'payment_preferences' => $paymentPreferences,
            'taxes' => $taxes
        ];
    }
}