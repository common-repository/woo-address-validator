<?php
/**
 * Handles the Woocommerce actions.
 *
 * @package WCAV
 */

/**
 * Class WCAV_WC
 */
class WCAV_WC {

	/**
	 * The URL to the plugin.
	 *
	 * @var string
	 */
	private $plugin_url = '';

	/**
	 * The path to the plugin.
	 *
	 * @var string
	 */
	private $plugin_path = '';

	/**
	 * The WCAV_Validator.
	 *
	 * @var WCAV_Validator | NULL
	 */
	private $validator = NULL;

	/**
	 * @var bool
	 */
	private $is_valid_shipping;

	/**
	 * @var bool
	 */
	private $is_valid_billing;

	/**
	 * @var string
	 */
	private $shipping_status;

	/**
	 * @var string
	 */
	private $billing_status;

 	/**
	 * @var bool
	 */
	private $allow_invalidaddr;

	/**
	 * Setup the admin.
	 *
	 * @param string         $plugin_path The path to the plugin.
	 * @param string         $plugin_url The URL to the plugin.
	 * @param WCAV_Validator $validator The Validator.
	 */
	public function setup( $plugin_path, $plugin_url, WCAV_Validator $validator, $allow_invalidaddr ) {

		$this->plugin_path       = $plugin_path;
		$this->plugin_url        = $plugin_url;
		$this->validator         = $validator;
		$this->allow_invalidaddr = $allow_invalidaddr;

		if ( function_exists('woocommerce_gzdp_multistep_checkout_validate_address') ) {
			add_action( 'woocommerce_gzdp_multistep_checkout_validate_address', array( $this, 'validate_gz' ) );
		} else {
			add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate' ) );
			add_action( 'woocommerce_before_checkout_form', array( $this, 'scripts' ) );
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'add_order_metadata' ) );
		}

		add_action( 'template_redirect', array( $this, 'edit_address_validation' ), 9 );}

	/**
	 * Enqueue scripts on checkout form
	 *
	 * @wp-hook woocommerce_before_checkout_form
	 */
	function scripts() {

		$min = '.min';
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$min = '';
		}
		wp_enqueue_script( 'wcav-checkout', $this->plugin_url . '/assets/js/checkout' . $min . '.js', array( 'jquery' ), NULL );
		?><style>#wcav-data{display:none;}</style><?php
	}

	/**
	 * Validate addresses, when they are edited on the myaccount page.
	 *
	 * @see woocommerce/includes/class-wc-form-handler.php for reference.
	 */
	public function edit_address_validation() {

		if (
			empty( $_POST['_wpnonce'] )  // Input var okay.
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'woocommerce-edit_address' )  // Input var okay.
			|| empty( $_POST['action'] ) // Input var okay.
			|| 'edit_address' !== sanitize_text_field( wp_unslash( $_POST['action'] ) ) // Input var okay.
		) {
			return;
		}

		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return;
		}

		$field_keys = array(
			'billing_address_1',
			'billing_address_2',
			'billing_postcode',
			'billing_city',
			'billing_state',
			'billing_country',
			'shipping_address_1',
			'shipping_address_2',
			'shipping_postcode',
			'shipping_city',
			'shipping_state',
			'shipping_country',
		);

		$posted = array();
		foreach ( $field_keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) { // Input var okay.
				$posted[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) ); // Input var okay.
			}
		}
		$this->validate( $posted );

	}

	/**
	 * Check the address(es) and add a wc_add_notice if an address is not valid.
	 *
	 * @see edit_address_validation()
	 * @wp-hook woocommerce_after_checkout_validation
	 *
	 * @param array $posted The checkout details.
	 */
	public function validate( $posted ) {

		$is_first_round = TRUE;
		$cookie = '';
		if ( ! empty( $_COOKIE['wcav'] ) ) {
			$cookie = $_COOKIE['wcav'];
			setcookie( 'wcav', null, -1 );
			$is_first_round = FALSE;
		}
		$cookie = json_decode( $cookie );
		$set_cookie = FALSE;
		$this->is_valid_billing = TRUE;
		$this->is_valid_shipping = TRUE;
		if ( isset( $posted['billing_address_1'] ) ) {
			$address = new WCAV_Address(
				$posted['billing_address_1'],
				$posted['billing_address_2'],
				$posted['billing_postcode'],
				$posted['billing_city'],
				$posted['billing_state'],
				$posted['billing_country']
			);
			$this->is_valid_billing = $this->validator->run( $address );
			$this->billing_status = $this->validator->get_status();

			if ( ! $this->is_valid_billing && $is_first_round ) {
				$this->wc_notice();
				if ( $this->allow_invalidaddr && 'INVALID' === $this->validator->get_status() || 'SUSPECT' === $this->validator->get_status() ) {
					$set_cookie = TRUE;
					$address = $this->validator->get_sanitized_address();
					$cookie['billing'] = $address->get_address();
				}
			}
		}

		if ( isset( $posted['shipping_address_1'] ) ) {
			$address = new WCAV_Address(
				$posted['shipping_address_1'],
				$posted['shipping_address_2'],
				$posted['shipping_postcode'],
				$posted['shipping_city'],
				$posted['shipping_state'],
				$posted['shipping_country']
			);
			$this->is_valid_shipping = $this->validator->run( $address );

			$this->shipping_status = $this->validator->get_status();
			if ( ! $this->is_valid_shipping && $is_first_round ) {

			   if ( $this->allow_invalidaddr && 'INVALID' === $this->validator->get_status() || 'SUSPECT' === $this->validator->get_status() ) {
					$set_cookie = TRUE;
					$address = $this->validator->get_sanitized_address();
					$cookie['shipping'] = $address->get_address();
				}

				// Show the notice only, if the billing address is correct.
				if ( $this->is_valid_billing ) {
					$this->wc_notice();
				}
			}
		}

		if ( $set_cookie ) {
			setrawcookie( 'wcav', rawurlencode( wp_json_encode( $cookie ) ) );
		}
	}

	/**
	 * Check the address(es) and add a wc_add_notice if an address is not valid.
	 *
	 * @wp-hook woocommerce_gzdp_multistep_checkout_validate_address'
	 *
	 * @param array $posted The checkout details.
	 */
	public function validate_gz( $posted ) {

		$is_first_round = TRUE;
		$cookie = '';
		if ( ! empty( $_COOKIE['wcav'] ) ) {
			$cookie = $_COOKIE['wcav'];
			setcookie( 'wcav', null, -1 );
			$is_first_round = FALSE;
		}
		$cookie = json_decode( $cookie );
		$set_cookie = FALSE;
		$this->is_valid_billing = TRUE;
		$this->is_valid_shipping = TRUE;
		if ( isset( $posted['billing_address_1'] ) ) {
			$address = new WCAV_Address(
				$posted['billing_address_1'],
				$posted['billing_address_2'],
				$posted['billing_postcode'],
				$posted['billing_city'],
				$posted['billing_state'],
				$posted['billing_country']
			);
			$this->is_valid_billing = $this->validator->run( $address );
			$this->billing_status = $this->validator->get_status();

			if ( ! $this->is_valid_billing && $is_first_round ) {
				$this->wc_notice();
				if ( $this->allow_invalidaddr && 'INVALID' === $this->validator->get_status() || 'SUSPECT' === $this->validator->get_status() ) {
					$set_cookie = TRUE;
					$address = $this->validator->get_sanitized_address();
					$cookie['billing'] = $address->get_address();
				}
			}
		}

		if ( isset( $posted['shipping_address_1'] ) ) {
			$address = new WCAV_Address(
				$posted['shipping_address_1'],
				$posted['shipping_address_2'],
				$posted['shipping_postcode'],
				$posted['shipping_city'],
				$posted['shipping_state'],
				$posted['shipping_country']
			);
			$this->is_valid_shipping = $this->validator->run( $address );

			$this->shipping_status = $this->validator->get_status();
			if ( ! $this->is_valid_shipping && $is_first_round ) {

			   if ( $this->allow_invalidaddr && 'INVALID' === $this->validator->get_status() || 'SUSPECT' === $this->validator->get_status() ) {
					$set_cookie = TRUE;
					$address = $this->validator->get_sanitized_address();
					$cookie['shipping'] = $address->get_address();
				}

				// Show the notice only, if the billing address is correct.
				if ( $this->is_valid_billing ) {
					$this->wc_notice();
				}
			}
		}

		if ( $set_cookie ) {
			setrawcookie( 'wcav', rawurlencode( wp_json_encode( $cookie ) ) );
		}
	}

	/**
	 * Add a Woocommerce Notice
	 */
	public function wc_notice() {

 		if ( 'SUSPECT' === strtoupper( $this->validator->get_status() ) ) {
 			$string = __( 'We have corrected your address. Please check the address again.', 'woo-address-validator' );
		} elseif ( $this->allow_invalidaddr && 'INVALID' == strtoupper( $this->validator->get_status() ) ) {
			$string = __( 'Your address could not be verified. Please check the address again.', 'woo-address-validator' );
 		} else {
 			$string = __( 'Please enter a valid address.', 'woo-address-validator' );
 		}
		wc_add_notice( $string, 'error' );
	}

	/**
	 * Add the address status to the meta data.
	 *
	 * @param int $post_id The order id.
	 */
	public function add_order_metadata( $post_id ) {

		update_post_meta( $post_id, 'wcav_shipping_status', $this->shipping_status );
		update_post_meta( $post_id, 'wcav_billing_status', $this->billing_status );
	}
}
