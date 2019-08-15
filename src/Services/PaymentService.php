<?php

namespace SkrillDevelopment\Services;
use Plenty\Plugin\Log\Loggable;

use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Plugin\Templates\Twig;

use SkrillDevelopment\Constants\SessionKeys;
use SkrillDevelopment\Services\GatewayService;

/**
* Class PaymentService
* @package SkrillDevelopment\Services
*/
class PaymentService
{
	use Loggable;

	/**
	 *
	 * @var gatewayService
	 */
	private $gatewayService;

	/**
	 *
	 * @var sessionStorageFactory
	 */
	private $sessionStorageFactory;

	/**
	 *
	 * @var basketService
	 */
	private $basketService;

	/**
	 *
	 * @var twig
	 */
	private $twig;

	/**
	 * Constructor.
	 *
	 * @param GatewayService $gatewayService
	 */
	public function __construct(
					GatewayService $gatewayService,
					FrontendSessionStorageFactoryContract $sessionStorageFactory,
					BasketServiceContract $basketService,
					Twig $twig
	) {
		$this->gatewayService = $gatewayService;
		$this->sessionStorageFactory = $sessionStorageFactory;
		$this->basketService = $basketService;
		$this->twig = $twig;
	}

	/**
	 * Returns the payment method's content.
	 *
	 * @param Basket $basket
	 * @param PaymentMethod $paymentMethod
	 * @return array
	 */
	public function getPaymentContent(PaymentMethod $paymentMethod, int $mopId): array
	{
		$this->getLogger(__METHOD__)->error('SkrillDevelopment:paymentMethod', $paymentMethod);

		$methodInstance = $this->paymentHelper->getPaymentMethodInstance($paymentMethod);

		$type = $methodInstance->getReturnType();
		$value = '';
		$sidResult = null;

		$basket     = $this->basketService->getBasket();

		$skrillSettings = [
			'merchantId' => '58010731',
			'merchantAccount' => 'blagovest.georgiev@payreto.com',
			'recipient' => 'Shop Recipient',
			'logoUrl' => 'https://www.skrill.com/fileadmin/content/images/brand_centre/Skrill_Logos/skrill-200x87_en.gif',
			'apiPassword' => 'XSt9CdTutqEWIL',
			'secretWord' => 'payreto'
		];

		if (empty($skrillSettings['merchantId'])
			|| empty($skrillSettings['merchantAccount'])
			|| empty($skrillSettings['recipient'])
			|| empty($skrillSettings['logoUrl'])
			|| empty($skrillSettings['apiPassword'])
			|| empty($skrillSettings['secretWord']))
		{
			return [
				'type' => GetPaymentMethodContent::RETURN_TYPE_ERROR,
				'content' => 'The Merchant SkrillDevelopment configuration is not complete. Please contact the Merchant'
			];
		}

		$this->getLogger(__METHOD__)->error('SkrillDevelopment:basket', $basket);

		try
		{
			$sidResult = $this->sendPaymentRequest($basket, $paymentMethod, $mopId, $skrillSettings);
		}
		catch (\Exception $e)
		{
			$this->getLogger(__METHOD__)->error('SkrillDevelopment:getSidResult', $e);
			return [
				'type' => GetPaymentMethodContent::RETURN_TYPE_ERROR,
				'content' => 'An error occurred while processing your transaction. Please contact our support.'
			];
		}

		$this->getLogger(__METHOD__)->error('SkrillDevelopment:sidResult', $sidResult);

		if ($skrillSettings['display'] == 'REDIRECT')
		{
			$value = $this->gatewayService->getPaymentPageUrl($sidResult);
			$type = GetPaymentMethodContent::RETURN_TYPE_REDIRECT_URL;
		}
		else
		{
			$parameters = [
                'sid' => $sidResult
            ];
            $value      = $this->renderPaymentForm($methodInstance->getFormTemplate(), $parameters);
		}

		return [
			'type' => $type,
			'value' => $value
		];
	}

	/**
     * Renders the given template injecting the parameters
     *
     * @param string $template
     * @param array $parameters
     * @return string
     */
    protected function renderPaymentForm(string $template, array $parameters = []): string
    {
        return $this->twig->render($template, $parameters);
    }

	public function sendPaymentRequest(
		Basket $basket,
		string $paymentMethod,
		int $mopId,
		array $additionalParams = []
	)
	{
		$transactionId = $this->createNewTransactionId($basket);
		$skrillParameters = $this->prepareSkrillParameters($basket, $paymentMethod, $mopId, $transactionId, $additionalParams);
		$sidResult = $this->gatewayService->getSidResult($skrillParameters);

		return $sidResult;
	}

	/**
     * @param Basket $basket
     * @param string $paymentMethod
     * @param int $mopId
     * @param string $transactionId
     * @param array $additionalParams
     * @throws RuntimeException
     */
    private function prepareSkrillParameters(
        Basket $basket,
        string $paymentMethod,
        int $mopId,
        string $transactionId,
        array $additionalParams = [])
    {
        $basketArray = $basket->toArray();

        // set customer personal information & address data
        $addresses      = $this->basketService->getCustomerAddressData();
        $billingAddress = $addresses['billing'];

        if ($this->sessionStorageFactory->getCustomer()->showNetPrice) {
            $basketArray['itemSum']        = $basketArray['itemSumNet'];
            $basketArray['basketAmount']   = $basketArray['basketAmountNet'];
            $basketArray['shippingAmount'] = $basketArray['shippingAmountNet'];
        }

        $parameters = [
			'pay_to_email' => $additionalParams['merchantAccount'],
			'recipient_description' => $additionalParams['recipient'],
			'transaction_id' => $transactionId,
			'return_url' => $this->paymentHelper->getDomain().
				'/payment/skrilldevelopment/return?transactionId='.$transactionId,
			'status_url' => $this->paymentHelper->getDomain().
				'/payment/skrilldevelopment/status?transactionId='.$transactionId.
				'&paymentKey='.$paymentMethod->paymentKey,
			'cancel_url' => $this->paymentHelper->getDomain().'/checkout',
			'language' => $this->getLanguage(),
			'logo_url' => $additionalParams['logoUrl'],
			'prepare_only' => 1,
			'firstname' => $billingAddress->firstName,
			'lastname' => $billingAddress->lastName,
			'address' => $billingAddress->address,
			'postal_code' => $billingAddress->postalCode,
			'city' => $billingAddress->city,
			'country' => $billingAddress->country,
			'amount' => $orderData->order->amounts[0]->invoiceTotal,
			'currency' => $basketArray['currency'],
			'detail1_description' => 'Order',
			'detail1_text' => $transactionId,
			'detail2_description' => "Order Amount",
			'detail2_text' => $basketArray['itemSum'] . ' ' . $basketArray['currency'],
			'detail3_description' => "Shipping",
			'detail3_text' => $basketArray['shippingAmount'] . ' ' . $basketArray['currency'],
			'merchant_fields' => 'platform',
			'platform' => '21477252',
		];

		if ($paymentMethod->paymentKey == 'SKRILL_ACC')
		{
			$parameters['payment_methods'] = 'VSA, MSC, AMX';
		}
		elseif ($paymentMethod->paymentKey != 'SKRILL_APM')
		{
			$parameters['payment_methods'] = str_replace('SKRILLDEVELOPMENT_', '', $paymentMethod->paymentKey);
		}
		if (!empty($additionalParams['merchantEmail']))
		{
			$parameters['status_url2'] = 'mailto:' . $additionalParams['merchantEmail'];
		}

		return $parameters;
    }

	/**
     * Creates transactionId and store it in the customer session to fetch the correct transaction later.
     *
     * @param Basket $basket
     * @return string
     */
    private function createNewTransactionId(Basket $basket): string
    {
    	$transactionId = time() . $this->getRandomNumber(4) . $basket->id;;
        $this->sessionStorageFactory->getPlugin()->setValue(SessionKeys::SESSION_KEY_TXN_ID, $transactionId);
        return $transactionId;
    }

    /**
	 * Returns a random number with length as parameter given.
	 *
	 * @param int $length
	 * @return string
	 */
	private function getRandomNumber($length)
	{
		$result = '';

		for ($i = 0; $i < $length; $i++)
		{
			$result .= rand(0, 9);
		}

		return $result;
	}
}