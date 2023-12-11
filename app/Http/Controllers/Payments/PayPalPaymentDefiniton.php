<?php

namespace App\Http\Controllers\Payments;

use PayPal\Api\ChargeModel;
use PayPal\Api\Currency;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\PaymentDefinition as PayPalPaymentDefinition;

class PaymentDefinition
{
    /**
     * Crea una definición de pago recurrente para la suscripción de PayPal.
     *
     * @return PayPalPaymentDefinition
     */
    public static function createSubscriptionPaymentDefinition()
    {
        $paymentDefinition = new PayPalPaymentDefinition();
        $paymentDefinition->setName('Regular Payments')
            ->setType('REGULAR')
            ->setFrequency('Month')
            ->setFrequencyInterval(1)
            ->setCycles(0);

        $currency = new Currency();
        $currency->setCurrency('MXN'); // Moneda mexicana

        $amount = new Currency(['value' => 200.00, 'currency' => 'MXN']); // Valor de la suscripción
        $paymentDefinition->setAmount($amount);

        $chargeModel = new ChargeModel();
        $chargeModel->setType('TAX') // Tipo de cargo (puede ser 'TAX', 'SHIPPING', 'FEE', etc.)
            ->setAmount(new Currency(['value' => 32.00, 'currency' => 'MXN'])); // Monto del impuesto

        $paymentDefinition->setChargeModels([$chargeModel]);

        return $paymentDefinition;
    }
}
