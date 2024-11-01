<?php
/**
 * The address validator API
 *
 * @package WCAV
 */

/**
 * Class WCAV_API
 */
class WCAV_API {

	/**
	 * The API endpoint.
	 *
	 * @var string
	 */
	private $endpoint = 'https://api.address-validator.net/api/verify';

	/**
	 * The API Key.
	 *
	 * @var string
	 */
	private $api_key = '';

	/**
	 * WCAV_API constructor.
	 *
	 * @param string $api_key The API Key.
	 */
	public function __construct( $api_key ) {

		$this->api_key = $api_key;
	}

	/**
	 * Validate an Address.
	 *
	 * @param WCAV_Address $address The address.
	 *
	 * @return array
	 */
	public function validate( WCAV_Address $address ) {

		$params = $address->get_address();
		$params['APIKey'] = $this->api_key;
      $params['PluginVersion'] = WCAV_PLUGIN_CURRENT_VERSION;

		$cache_key = md5( implode( ',', $params ) );
		$result = wp_cache_get( $cache_key, 'wcav' );

		if ( FALSE !== $result ) {
			return $result;
		}

		$result = wp_safe_remote_post(
			$this->endpoint,
			array(
				'body' => $params,
			)
		);

		wp_cache_set( $cache_key, $result, 'wcav' );
		return $result;
	}
}
