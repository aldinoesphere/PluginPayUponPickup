<?php
 
namespace SkrillDevelopment\Methods;
 
use Plenty\Plugin\ConfigRepository;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodService;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
 
/**
 * Class SkrillDevelopmentPaymentMethod
 * @package SkrillDevelopment\Methods
 */
class AbstractPaymentMethod extends PaymentMethodService
{
    const RETURN_TYPE = GetPaymentMethodContent::RETURN_TYPE_REDIRECT_URL;
    const INITIALIZE_PAYMENT = true;
    const FORM_TEMPLATE = '';

    protected $name = '';

    /**
     * Check the configuration if the payment method is active
     * Return true if the payment method is active, else return false
     *
     * @param ConfigRepository $configRepository
     * @param BasketRepositoryContract $basketRepositoryContract
     * @return bool
     */
    public function isActive( ConfigRepository $configRepository,
                              BasketRepositoryContract $basketRepositoryContract):bool
    {
        /** @var bool $active */
        $active = true;
 
        /** @var Basket $basket */
        $basket = $basketRepositoryContract->load();
 
        return $active;
    }
 
    /**
     * Get the name of the payment method. The name can be entered in the config.json.
     *
     * @param ConfigRepository $configRepository
     * @return string
     */
    public function getName( ConfigRepository $configRepository ):string
    {
        $name = $configRepository->get('SkrillDevelopment.name');
 
        if(!strlen($name))
        {
            $name = 'Skrill Development';
        }
 
        return $this->name;
 
    }
 
    /**
     * Get the path of the icon. The URL can be entered in the config.json.
     *
     * @param ConfigRepository $configRepository
     * @return string
     */
    public function getIcon( ConfigRepository $configRepository ):string
    {
        return '';
    }
 
    /**
     * Get the description of the payment method. The description can be entered in the config.json.
     *
     * @param ConfigRepository $configRepository
     * @return string
     */
    public function getDescription( ConfigRepository $configRepository ):string
    {
        if($configRepository->get('SkrillDevelopment.infoPage.type') == 1)
        {
            return $configRepository->get('SkrillDevelopment.infoPage.intern');
        }
        elseif ($configRepository->get('SkrillDevelopment.infoPage.type') == 2)
        {
            return $configRepository->get('SkrillDevelopment.infoPage.extern');
        }
        else
        {
          return '';
        }
    }

    /**
     * @inheritdoc
     */
    public function isExpressCheckout(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getFee(): float
    {
        return 0.00;
    }

    /**
     * @inheritdoc
     */
    public function isSelectable(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function isSwitchableTo(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function isSwitchableFrom(): bool
    {
        return false;
    }

    /**
     * Returns the type of the result returned by the payment method initialization.
     *
     * @return string
     */
    public function getReturnType(): string
    {
        return static::RETURN_TYPE;
    }

    /**
     * Returns true if the payment has to be initialized with transaction (i.e. to fetch redirect url).
     *
     * @return bool
     */
    public function hasToBeInitialized(): bool
    {
        return static::INITIALIZE_PAYMENT;
    }

    /**
     * Returns the template of the payment form.
     *
     * @return string
     */
    public function getFormTemplate(): string
    {
        return static::FORM_TEMPLATE;
    }
}