<?php
namespace SkrillDevelopment\Configs;
use SkrillDevelopment\Constants\Plugin;
use SkrillDevelopment\Methods\WltPaymentMethod;

class MethodConfig extends BaseConfig
{
    const ARRAY_KEY_CONFIG_KEY = 'config_key';
    const ARRAY_KEY_DEFAULT_NAME = 'default_name';
    const ARRAY_KEY_KEY = 'key';

    const NO_CONFIG_KEY_FOUND = 'no_config_key_found';
    const NO_DEFAULT_NAME_FOUND = 'no_default_name_found';
    const NO_KEY_FOUND = 'no_key_found';

    /**
     * @var array
     */
    public static $paymentMethods = [
        WltPaymentMethod::class => [
            self::ARRAY_KEY_KEY => WltPaymentMethod::KEY,
            self::ARRAY_KEY_DEFAULT_NAME => WltPaymentMethod::DEFAULT_NAME
        ]
    ];

    /**
     * Returns the available payment methods and their helper strings (config-key, payment-key, default name).
     *
     * @return string[]
     */
    public static function getPaymentMethods()
    {
        return array_keys(static::$paymentMethods);
    }

    /**
     * @param string $paymentMethod
     *
     * @return string
     */
    public function getPaymentMethodDefaultName(string $paymentMethod): string
    {
        $prefix = Plugin::NAME . ' - ';
        $name = static::$paymentMethods[$paymentMethod][self::ARRAY_KEY_DEFAULT_NAME] ?? self::NO_DEFAULT_NAME_FOUND;

        return $prefix . $name;
    }

    /**
     * This is also used within the PaymentHelper class, so it must be public.
     *
     * @param string $paymentMethod
     *
     * @return string
     */
    public function getPaymentMethodKey(string $paymentMethod): string
    {
        return static::$paymentMethods[$paymentMethod][self::ARRAY_KEY_KEY] ?? self::NO_KEY_FOUND;
    }
}
