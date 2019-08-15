<?php
 
namespace SkrillDevelopment\Providers;
 
use Plenty\Plugin\ServiceProvider;
use Plenty\Plugin\Events\Dispatcher;
use Plenty\Modules\Payment\Events\Checkout\ExecutePayment;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use Plenty\Modules\Basket\Events\Basket\AfterBasketCreate;
use Plenty\Modules\Basket\Events\Basket\AfterBasketChanged;
use Plenty\Modules\Basket\Events\BasketItem\AfterBasketItemAdd;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodContainer;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;

use SkrillDevelopment\Helper\PaymentHelper;
use SkrillDevelopment\Methods\SkrillDevelopmentPaymentMethod;
use SkrillDevelopment\Configs\MethodConfig;
use SkrillDevelopment\Services\PaymentService;
 
/**
 * Class SkrillDevelopmentServiceProvider
 * @package SkrillDevelopment\Providers
 */
class SkrillDevelopmentServiceProvider extends ServiceProvider
{
    public function register()
    {
 
    }
 
    /**
     * Boot additional services for the payment method
     *
     * @param PaymentHelper $paymentHelper
     * @param PaymentMethodContainer $payContainer
     * @param Dispatcher $eventDispatcher
     */
    public function boot(
        PaymentHelper $paymentHelper,
        PaymentMethodContainer $methodContainer,
        Dispatcher $eventDispatcher,
        PaymentService $paymentService,
        PaymentMethodRepositoryContract $paymentMethodService
    ) {

        $paymentHelper->createMopsIfNotExist();
        // Register the Skrill Development payment method in the payment method container
        foreach (MethodConfig::getPaymentMethods() as $paymentMethodClass) {
            // register the payment method in the payment method container
            $methodContainer->register(
                $paymentHelper->getPluginPaymentMethodKey($paymentMethodClass),
                $paymentMethodClass,
                $paymentHelper->getPaymentMethodEventList()
            );
        }
 
        // Listen for the event that gets the payment method content
        $eventDispatcher->listen(GetPaymentMethodContent::class,
            function(
                GetPaymentMethodContent $event
            ) use (
                $paymentHelper,
                $paymentMethodService,
                $paymentService
            ){
                $mop = $event->getMop();
                $paymentMethod = $paymentMethodService->findByPaymentMethodId($mop);
                if($paymentHelper->isSkrillDevelopmentPaymentMopId($mop))
                {
                    $return = $paymentService->getPaymentContent('WltPaymentMethod::class', $mop);
                    $event->setValue($return['value']);
                    $event->setType($return['type']);
                }
            }
        );
 
        // Listen for the event that executes the payment
        $eventDispatcher->listen(ExecutePayment::class,
           function(ExecutePayment $event) use( $paymentHelper)
           {
               if($paymentHelper->isSkrillDevelopmentPaymentMopId($mop))
               {
                   $event->setValue('<h1>Skrill Development<h1>');
                   $event->setType('continue');
               }
           });
   }
}