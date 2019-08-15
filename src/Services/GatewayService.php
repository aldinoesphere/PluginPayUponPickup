<?php
namespace SkrillDevelopment\Services;

use Plenty\Plugin\Log\Loggable;

/**
* Class GatewayService
* @package SkrillDevelopment\Services
*/
class GatewayService
{
	use Loggable;

	/**
	 * @var string
	 */
	protected $skrillPayUrl = 'https://pay.skrill.com';

	/**
	 * @var string
	 */
	protected $skrillQueryUrl = 'https://www.skrill.com/app/query.pl';

	/**
	 * @var string
	 */
	protected $skrillRefundUrl = 'https://www.skrill.com/app/refund.pl';

	/**
	 * Get SkrillDevelopment Payment Page Url
	 *
	 * @param string $sid
	 * @return string
	 */
	public function getPaymentPageUrl($sid)
	{
		$paymentPageUrl = $this->skrillPayUrl.'?sid='.$sid;
		$this->getLogger(__METHOD__)->error('SkrillDevelopment:paymentPageUrl', $paymentPageUrl);

		return $paymentPageUrl;
	}

	/**
	 * Get gateway response
	 *
	 * @param string $url
	 * @param array $parameters
	 * @throws \Exception
	 * @return string
	 */
	private function getGatewayResponse($url, $parameters)
	{
		$postFields = http_build_query($parameters, '', '&');

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLINFO_HEADER_OUT, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/x-www-form-urlencoded;charset=UTF-8'));
		curl_setopt($curl, CURLOPT_POST, count($parameters));
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);

		$response = curl_exec($curl);
		if (curl_errno($curl))
		{
			throw new \Exception(curl_error($curl));
		}
		curl_close($curl);

		return $response;
	}

	/**
	 * Get Sid from gateway to use at payment page url
	 *
	 * @param array $parameters
	 * @throws \Exception
	 * @return string
	 */
	public function getSidResult($parameters)
	{
		$response = $this->getGatewayResponse($this->skrillPayUrl, $parameters);

		if (!$this->isMd5Valid($response))
		{
			throw new \Exception('Sid is not valid : ' . $response);
		}

		return $response;
	}

	/**
	 * get currenty payment status from gateway
	 *
	 * @param $parameters
	 * @throws \Exception
	 * @return array
	 */
	public function getPaymentStatus($parameters)
	{
		$parameters['action'] = 'status_trn';
		$response = $this->getGatewayResponse($this->skrillQueryUrl, $parameters);

		$this->getLogger(__METHOD__)->error('SkrillDevelopment:response', $response);

		$responseCode = (int) substr($response, 0, 3);
		if ($responseCode == 401)
		{
			if (strpos($response, 'Cannot login') !== false)
			{
				throw new \Exception('Please check MQI/API password');
			}
			elseif (strpos($response, 'Your account is currently locked') !== false)
			{
				$message = "Your account is currently locked. Please contact our Merchant Team: merchantservices@skrill.com";
				throw new \Exception($message);
			}
			throw new \Exception('Get payment status failed!');
		}

		$responseInArray = $this->setResponseToArray($response);

		if (!$responseInArray)
		{
			throw new \Exception('Get payment status failed!');
		}
		return $responseInArray;
	}

	/**
	 * set response from string to array
	 *
	 * @param  array $response
	 * @return boolean | array
	 */
	private function setResponseToArray($response)
	{
		$responses = explode("\n", $response);
		if (!empty($responses[1]))
		{
			$string = 'header='.$responses[0].'&'.$responses[1];
			$strings = explode('&', $string);
			foreach ($strings as $key => $value)
			{
				$values = explode('=', $value);
				$responseInArray[urldecode($values[0])] = urldecode($values[1]);
			}
			return $responseInArray;
		}
		else
		{
			return false;
		}
	}

	/**
	 * send request and get refund status from gateway
	 *
	 * @param $parameters
	 * @throws \Exception
	 * @return xml
	 */
	public function doRefund($parameters)
	{
		$parameters['action'] = 'prepare';
		$response = $this->getGatewayResponse($this->skrillRefundUrl, $parameters);

		$this->getLogger(__METHOD__)->error('SkrillDevelopment:prepare_response', $response);

		$xmlResponse = simplexml_load_string($response);
		$sid = (string) $xmlResponse->sid;

		if (!$this->isMd5Valid($sid))
		{
			throw new \Exception('Sid is not valid : ' . $response);
		}

		unset($parameters);
		$parameters['action'] = 'refund';
		$parameters['sid'] = $sid;

		$response = $this->getGatewayResponse($this->skrillRefundUrl, $parameters);

		$this->getLogger(__METHOD__)->error('SkrillDevelopment:refund_response', $response);

		return simplexml_load_string($response);
	}

	/**
	 * check if string is md5
	 *
	 * @return boolean
	 */
	private function isMd5Valid($string)
	{
		return preg_match('/^[a-f0-9]{32}$/', $string);
	}
}
