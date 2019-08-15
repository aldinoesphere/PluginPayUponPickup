<?php
 
namespace SkrillDevelopment\Helper;
 
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;
use Plenty\Modules\Payment\Method\Models\PaymentMethod;
use Plenty\Modules\Basket\Events\Basket\AfterBasketChanged;
use Plenty\Modules\Basket\Events\Basket\AfterBasketCreate;
use Plenty\Modules\Basket\Events\BasketItem\AfterBasketItemAdd;
use Plenty\Modules\Frontend\Events\FrontendLanguageChanged;
use Plenty\Modules\Frontend\Events\FrontendShippingCountryChanged;

use SkrillDevelopment\Constants\Plugin;
use SkrillDevelopment\Configs\MethodConfig;
 
/**
 * Class PaymentHelper
 *
 * @package SkrillDevelopment\Helper
 */
class PaymentHelper
{
    
    const NO_PAYMENTMETHOD_FOUND = -1;

    /**
     * @var PaymentMethodRepositoryContract $paymentMethodRepository
     */
    private $paymentMethodRepository;

    /** @var MethodConfig $methodConfig */
    private $methodConfig;
 
    /**
     * PaymentHelper constructor.
     *
     * @param PaymentMethodRepositoryContract $paymentMethodRepository
     */
    public function __construct(MethodConfig $methodConfig, PaymentMethodRepositoryContract $paymentMethodRepository)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->methodConfig            = $methodConfig;
    }

    /**
     * Create the payment method IDs that don't exist yet.
     */
    public function createMopsIfNotExist()
    {
        foreach ($this->methodConfig::getPaymentMethods() as $paymentMethod) {
            $this->createMopIfNotExists($paymentMethod);
        }
    }
 
    /**
     * Create the ID of the payment method if it doesn't exist yet
     */
    public function createMopIfNotExists(string $paymentMethodClass)
    {
        // Check whether the ID of the Skrill Development payment method has been created
        if ($this->getPaymentMethodId($paymentMethodClass) === self::NO_PAYMENTMETHOD_FOUND) {
            $paymentMethodData = [
                'pluginKey' => Plugin::KEY,
                'paymentKey' => $this->methodConfig->getPaymentMethodKey($paymentMethodClass),
                'name' => $this->methodConfig->getPaymentMethodDefaultName($paymentMethodClass)
            ];

            $this->paymentMethodRepository->createPaymentMethod($paymentMethodData);
        }
    }

    /**
     * Load the payment method ID for the given plugin key.
     *
     * @param string $paymentMethodClass
     *
     * @return int
     */
    public function getPaymentMethodId(string $paymentMethodClass)
    {
        $paymentMethods = $this->paymentMethodRepository->allForPlugin(Plugin::KEY);

        if (!empty($paymentMethods)) {
            /** @var PaymentMethod $payMethod */
            foreach ($paymentMethods as $payMethod) {
                if ($payMethod->paymentKey === $this->methodConfig->getPaymentMethodKey($paymentMethodClass)) {
                    return $payMethod->id;
                }
            }
        }

        return self::NO_PAYMENTMETHOD_FOUND;
    }

    /**
     * check if the mopId is Skrill mopId.
     *
     * @param number $mopId
     * @return bool
     */
    public function isSkrillDevelopmentPaymentMopId($mopId)
    {
        $paymentMethods = $this->paymentMethodRepository->allForPlugin(Plugin::KEY);

        if (!is_null($paymentMethods))
        {
            foreach ($paymentMethods as $paymentMethod)
            {
                if ($paymentMethod->id == $mopId)
                {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns the payment method key ('plugin_name::payment_key')
     *
     * @param string $paymentMethodClass
     *
     * @return string
     */
    public function getPluginPaymentMethodKey(string $paymentMethodClass): string
    {
        return Plugin::KEY . '::' . $this->methodConfig->getPaymentMethodKey($paymentMethodClass);
    }

    /**
     * Returns a list of events that should be observed.
     *
     * @return array
     */
    public function getPaymentMethodEventList(): array
    {
        return [
            AfterBasketChanged::class,
            AfterBasketItemAdd::class,
            AfterBasketCreate::class,
            FrontendLanguageChanged::class,
            FrontendShippingCountryChanged::class
        ];
    }

    /**
     * get domain from webstoreconfig.
     *
     * @return string
     */
    public function getDomain()
    {
        $webstoreHelper = pluginApp(\Plenty\Modules\Helper\Services\WebstoreHelper::class);
        $webstoreConfig = $webstoreHelper->getCurrentWebstoreConfiguration();
        $domain = $webstoreConfig->domainSsl;

        return $domain;
    }

    /**
     * @param string $paymentMethod
     * @return PaymentMethodContract|null
     */
    public function getPaymentMethodInstance(string $paymentMethod)
    {
        /** @var PaymentMethodContract $instance */
        $instance = null;

        if ($paymentMethod) {
            $instance = pluginApp($paymentMethod);
        }
        return $instance;
    }
}