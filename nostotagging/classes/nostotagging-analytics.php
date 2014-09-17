<?php

/**
 * Helper class for gathering analytics about the module usage with Google Measurement Protocol.
 *
 * @link https://developers.google.com/analytics/devguides/collection/protocol/v1/
 */
class NostoTaggingAnalytics
{
	const HIT_TYPE_EVENT = 'event';
	const HIT_TYPE_PAGEVIEW = 'pageview';
	const CONFIG_KEY_CLIENT_ID = 'NOSTOTAGGING_ANALYTICS_CLIENT_ID';

	/**
	 * @var string the Google Measurement Protocol API endpoint.
	 */
	protected static $api_end_point = 'https://ssl.google-analytics.com/collect';

	/**
	 * @var string The tracking ID that all collected data is associated with.
	 */
	protected static $tracking_id = 'UA-54881067-1';

	/**
	 * @var string This anonymously identifies a particular user, device, or browser instance.
	 * @see NostoTaggingAnalytics::getClientId
	 */
	protected static $client_id ;

	/**
	 * Getter for the client id.
	 *
	 * @return string
	 */
	public static function getClientId()
	{
		if (self::$client_id !== null)
			return self::$client_id;

		$client_id = (string)Configuration::get(self::CONFIG_KEY_CLIENT_ID);
		if (empty($client_id)) {
			$client_id = self::uuid();
			Configuration::updateGlobalValue(self::CONFIG_KEY_CLIENT_ID, $client_id);
		}

		return self::$client_id = $client_id;
	}

	/**
	 * Returns a new UUID (version 4).
	 *
	 * @return string
	 * @link http://www.ietf.org/rfc/rfc4122.txt
	 */
	public static function uuid() {
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			// 16 bits for "time_mid"
			mt_rand(0, 0xffff),
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand(0, 0x0fff) | 0x4000,
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,
			// 48 bits for "node"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}

	/**
	 * Tracks a page view.
	 *
	 * @return bool
	 * @link https://developers.google.com/analytics/devguides/collection/protocol/v1/devguide#page
	 */
	public static function trackPageView()
	{
		$request = new NostoTaggingHttpRequest();
		$response = $request->post(
			self::$api_end_point,
			array('Content-type: application/x-www-form-urlencoded'),
			http_build_query(array(
				'v' => 1,
				't' => self::HIT_TYPE_PAGEVIEW,
				'tid' => self::$tracking_id,
				'cid' => self::getClientId(),
				'dh' => Configuration::get('PS_SHOP_DOMAIN'),
				'dp' => $_SERVER['REQUEST_URI'],
				'dt' => 'unknown'
			))
		);

		if ($response->getCode() !== 200)
			NostoTaggingLogger::log(
				__CLASS__.'::'.__FUNCTION__.' - Page view was not tracked by Google Measurement Protocol',
				NostoTaggingLogger::LOG_SEVERITY_INFO,
				$response->getCode()
			);

		return true;
	}

	/**
	 * Tracks an event.
	 *
	 * @param string $category Event category.
	 * @param string $action Event action.
	 * @param string $label (optional) Event label.
	 * @param int $value (optional) Event value.
	 * @return bool
	 * @link https://developers.google.com/analytics/devguides/collection/protocol/v1/devguide#event
	 */
	public static function trackEvent($category, $action, $label = '', $value = 0)
	{
		$request = new NostoTaggingHttpRequest();
		$response = $request->post(
			self::$api_end_point,
			array('Content-type: application/x-www-form-urlencoded'),
			http_build_query(array(
				'v' => 1,
				't' => self::HIT_TYPE_EVENT,
				'tid' => self::$tracking_id,
				'cid' => self::getClientId(),
				'ec' => $category,
				'ea' => $action,
				'el' => $label,
				'ev' => $value,
			))
		);

		if ($response->getCode() !== 200)
			NostoTaggingLogger::log(
				__CLASS__.'::'.__FUNCTION__.' - Event not tracked by Google Measurement Protocol',
				NostoTaggingLogger::LOG_SEVERITY_INFO,
				$response->getCode()
			);

		return true;
	}
} 