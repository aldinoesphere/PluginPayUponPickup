<?php

namespace SkrillDevelopment\Methods;

use Plenty\Plugin\Log\Loggable;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;

/**
* Class WltPaymentMethod
* @package SkrillDevelopment\Methods
*/
class WltPaymentMethod extends AbstractPaymentMethod
{
	use Loggable;

	const KEY = 'SKRILL_WLT';
	const DEFAULT_NAME = 'Skrill Wallet';
	const RETURN_TYPE = GetPaymentMethodContent::RETURN_TYPE_HTML;
    const INITIALIZE_PAYMENT = true;

	/**
	 * @var name
	 */
	protected $name = self::DEFAULT_NAME;

	/**
	 * @var logoFileName
	 */
	protected $logoFileName = 'wlt.png';

	/**
	 * @var settingsType
	 */
	protected $settingsType = 'skrill_wlt';
}
