<?php
/**
 * The address class.
 *
 * @package WCAV
 */

/**
 * Class WCAV_Address
 */
class WCAV_Address {

	/**
	 * The street address.
	 *
	 * @var string
	 */
	private $street_address = '';

	/**
	 * The additional address info.
	 *
	 * @var string
	 */
	private $additional_address_info = '';

	/**
	 * The postal code.
	 *
	 * @var string
	 */
	private $postal_code = '';

	/**
	 * The city.
	 *
	 * @var string
	 */
	private $city = '';

	/**
	 * The state.
	 *
	 * @var string
	 */
	private $state = '';

	/**
	 * The country code.
	 *
	 * @var string
	 */
	private $country_code = '';

	/**
	 * WCAV_Address constructor.
	 *
	 * @param string $street_address The street address.
	 * @param string $additional_address_info The additional address info.
	 * @param string $postal_code The postal code.
	 * @param string $city The city.
	 * @param string $state The state.
	 * @param string $country The country.
	 */
	public function __construct(
		$street_address,
		$additional_address_info,
		$postal_code,
		$city,
		$state,
		$country
	) {

		$this->set_street_address( $street_address );
		$this->set_additional_address_info( $additional_address_info );
		$this->set_postal_code( $postal_code );
		$this->set_city( $city );
		$this->set_state( $state );
		$this->set_country_code( $country );
	}

	/**
	 * Get the whole address.
	 *
	 * @return array The address array.
	 */
	public function get_address() {

		return array(
			'StreetAddress'         => $this->get_street_address(),
			'AdditionalAddressInfo' => $this->get_additional_address_info(),
			'PostalCode'            => $this->get_postal_code(),
			'City'                  => $this->get_city(),
			'State'                 => $this->get_state(),
			'CountryCode'           => $this->get_country_code(),
		);
	}

	/**
	 * Get street address.
	 *
	 * @return string
	 */
	public function get_street_address() {

		return $this->street_address;
	}

	/**
	 * Set the street address.
	 *
	 * @param string $street_address The new address.
	 */
	public function set_street_address( $street_address ) {

		$this->street_address = (string) $street_address;
	}

	/**
	 * Get the additional address info.
	 *
	 * @return string
	 */
	public function get_additional_address_info() {

		return $this->additional_address_info;
	}

	/**
	 * Set the additional address info.
	 *
	 * @param string $additional_address_info The additional address info.
	 */
	public function set_additional_address_info( $additional_address_info ) {

		$this->additional_address_info = (string) $additional_address_info;
	}

	/**
	 * Get the postal code.
	 *
	 * @return string
	 */
	public function get_postal_code() {

		return $this->postal_code;
	}

	/**
	 * Set the postal code.
	 *
	 * @param string $postal_code The postal code.
	 */
	public function set_postal_code( $postal_code ) {

		$this->postal_code = (string) $postal_code;
	}

	/**
	 * Get the city.
	 *
	 * @return string
	 */
	public function get_city() {

		return $this->city;
	}

	/**
	 * Set the city.
	 *
	 * @param string $city The city.
	 */
	public function set_city( $city ) {

		$this->city = (string) $city;
	}

	/**
	 * Get the country code.
	 *
	 * @return string
	 */
	public function get_country_code() {

		return $this->country_code;
	}

	/**
	 * Set the country code.
	 *
	 * @param string $country_code The country code.
	 */
	public function set_country_code( $country_code ) {

		$this->country_code = (string) $country_code;
	}

	/**
	 * Get the state.
	 *
	 * @return string
	 */
	public function get_state() {

		return $this->state;
	}

	/**
	 * Set the state.
	 *
	 * @param string $state The state.
	 */
	public function set_state( $state ) {

		$this->state = (string) $state;
	}
}
