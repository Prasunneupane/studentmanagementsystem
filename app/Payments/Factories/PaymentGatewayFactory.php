<?php

namespace App\Payments\Factories;

use App\Enums\PaymentGateway;
use App\Payments\Contracts\PaymentGatewayDriver;
use App\Payments\Gateways\EsewaGateway;
use App\Payments\Gateways\FonepayGateway;
use App\Payments\Gateways\KhaltiGateway;
use Illuminate\Contracts\Container\Container;

/**
 * Maps a validated PaymentGateway enum value to its driver. The enum is the
 * only input accepted — never a raw string/class name from the browser, so a
 * request can't ask the factory to instantiate an arbitrary class.
 *
 * Adding a gateway (e.g. Stripe later) means adding one case here and one
 * driver class — nothing else in this file or anywhere else changes.
 */
class PaymentGatewayFactory
{
    public function __construct(private Container $container) {}

    public function make(PaymentGateway $gateway): PaymentGatewayDriver
    {
        $driverClass = match ($gateway) {
            PaymentGateway::ESEWA => EsewaGateway::class,
            PaymentGateway::KHALTI => KhaltiGateway::class,
            PaymentGateway::FONEPAY => FonepayGateway::class,
        };

        return $this->container->make($driverClass);
    }
}
