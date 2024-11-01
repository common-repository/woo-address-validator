<?php
/**
 * Manages the admin interface.
 *
 * @package WCAV
 */

/**
 * Class WCAV_Admin
 */
class WCAV_Admin {

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
	 * The update nonce action.
	 *
	 * @var string
	 */
	private $action = 'wcav-upate';

	/**
	 * The update nonce action name.
	 *
	 * @var string
	 */
	private $action_name = 'wcav_nonce';

	/**
	 * The ajax action.
	 *
	 * @var string
	 */
	private $ajax_action = 'wcav-validate';

	/**
	 * The ajax nonce.
	 *
	 * @var string
	 */
	private $ajax_nonce = 'wcav-ajax';

	/**
	 * The options defaults
	 *
	 * @var array
	 */
	private $defaults = array();

	/**
	 * The validator object.
	 *
	 * @var WCAV_Validator
	 */
	private $validator;

	/**
	 * Setup the admin.
	 *
	 * @param string         $plugin_path The path to the plugin.
	 * @param string         $plugin_url The URL to the plugin.
	 * @param WCAV_Validator $validator The validator.
	 */
	public function setup( $plugin_path, $plugin_url, $validator ) {

		$this->plugin_path = $plugin_path;
		$this->plugin_url  = $plugin_url;
		$this->validator   = $validator;

 		$this->defaults = array(
 			'api-key' => '',
			'allow-invalidaddr' => 0
 		);

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
		if (
			current_user_can( 'manage_options' )
			&& ! empty( $_POST[ $this->action_name ] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $this->action_name ] ) ), $this->action )
		) {
 			$data = (array) wp_unslash( $_POST['wcav'] );
			// checkbox is missing in form data if unchecked, set to 0
			if ( ! isset( $data['allow-invalidaddr'] ) ) {
				$data[ 'allow-invalidaddr' ] = 0;
			}
			if ( $this->update( $data ) ) {
				$url = admin_url( 'options-general.php' );
				$url = add_query_arg( array( 'page' => 'wcav', 'updated' => 1 ), $url );
				wp_safe_redirect( $url );
				exit;
			} else {
				wp_die( esc_html__( 'Something went wrong.', 'woo-address-validator' ) );
			}
		}

		if ( 1 === (int) get_option( 'wcav-invalid-key', 0 ) ) {
			add_action( 'admin_notices', array( $this, 'render_invalid_key_notice' ) );
		}

		add_action( 'wp_ajax_' . $this->ajax_action, array( $this, 'ajax_validate_address' ) );
	}

	/**
	 * Update the settings..
	 *
	 * @param array $data The data array.
	 *
	 * @return bool
	 */
	public function update( $data ) {

		$sanitized = array();
		foreach ( $this->defaults as $key => $void ) {
			if ( ! isset( $data[ $key ] ) ) {
				return FALSE;
			}

			$sanitized[ $key ] = sanitize_text_field( $data[ $key ] );
		}

		update_option( 'wcav', $sanitized );
		update_option( 'wcav-invalid-key', 0 );

		return TRUE;
	}

	/**
	 * Add the settings page.
	 */
	public function admin_menu() {

		add_submenu_page( 'options-general.php', __( 'Address Validator', 'woo-address-validator' ), __( 'Address Validator', 'woo-address-validator' ), 'manage_options', 'wcav', array( $this, 'render' ) );
	}

	/**
	 * Register the admin scripts.
	 */
	public function register_scripts() {

		$min = '.min';
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$min = '';
		}
		wp_register_script( 'wcav', $this->plugin_url . 'assets/js/script' . $min . '.js', array( 'jquery' ) );
		$locale = array(
			'ajax'        => esc_js( esc_url( admin_url( 'admin-ajax.php' ) ) ),
			'action'      => esc_js( $this->ajax_action ),
			'nonce'       => esc_js( wp_create_nonce( $this->ajax_nonce ) ),
			'error_msg'   => esc_js( __( 'An error occured.', 'woo-address-validator' ) ),
			'modal_title' => esc_js( __( 'API Result', 'woo-address-validator' )  ),
		);
		wp_localize_script( 'wcav', 'WCAVLocale', $locale );
	}


	/**
	 * Render the settings page.
	 */
	public function render() {

		// The form action URL.
		$url = admin_url( 'options-general.php' );
		$url = add_query_arg( array( 'page' => 'wcav' ), $url );

		// The options.
		$options = get_option( 'wcav', array() );
		$options = wp_parse_args( $options, $this->defaults );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Woocommerce Address Validator', 'woo-address-validator' ); ?></h1>
			<?php if ( ! empty( $_GET['key-invalid'] ) && 1 === (int) $_GET['key-invalid'] ) : // Input var okay. ?>
				<p>
					<?php esc_html_e( 'It looks like your API key is invalid or depleted. Please check your key and the account balance.', 'woo-address-validator' ); ?>
					<?php echo wp_kses_post( sprintf( __( 'You can log into your Address-Validator account here <a href="%s">here</a>.', 'woo-address-validator' ), 'https://www.address-validator.net/dashboard/index.html' ) ); ?></p>
				</p>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( $url ); ?>">
				<?php wp_nonce_field( $this->action, $this->action_name ); ?>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="api-key"><?php esc_html_e( 'API Key', 'woo-address-validator' ); ?></label>
							</th>
							<td>
								<input type="text" name="wcav[api-key]" value="<?php echo esc_attr( $options['api-key'] ); ?>">
							</td>
						</tr>						
						<tr>
							<th scope="row">
								<label for="allow-invalidaddr"><?php esc_html_e( 'Allow INVALID Addresses', 'woo-address-validator' ); ?></label>
							</th>
							<td>
								<input type="checkbox" name="wcav[allow-invalidaddr]" value="1" <?php checked(1, $options['allow-invalidaddr'], true); ?> >
							</td>
						</tr>						
					</tbody>
				</table>
				<button class="button"><?php esc_html_e( 'Update', 'woo-address-validator' ); ?></button>
			</form>
			<hr>
			<p>
 				<?php
 				esc_html_e(
					'Allow INVALID Addresses will prompt the user to check an address that could not be validated, and allow them to continue (contributed by "Simply Charlotte Mason").', 'woo-address-validator'
				);
				?><br><br>
				<?php
				esc_html_e(
					'With Address-Validator you can easily verify any national or international postal address. Stop wasting time and money with undelivered packages or having to reach out to customers to get the correct addresses.', 'woo-address-validator'
				);
				?><br>
				<?php
				echo wp_kses_post(
					sprintf(
						__(
							'<a href="%1$s" target="_blank">Sign up here for your free API key</a> and get 625 free credits.','woo-address-validator'
						),
						'https://www.address-validator.net/free-trial-registration.html'
					)
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render the invalid key notice.
	 */
	public function render_invalid_key_notice() {
		?>
		<div class="notice notice-error is-dismissible">
			<p>
				<?php
				// The form action URL.
				$url = admin_url( 'options-general.php' );
				$url = add_query_arg( array( 'page' => 'wcav', 'key-invalid' => 1 ), $url );
				esc_html_e( 'Your Address-Validator API key is invalid or depleted.', 'woo-address-validator' );

				// Display the link to the settings page only, when we are not on the settings page.
				if ( empty( $_GET['page'] ) || 'wcav' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
					echo '<br>' . wp_kses_post(
							sprintf(
								__(
									'You can view/update the API key <a href="%s">on the settings page</a>.','woo-address-validator'
								),
								$url
							)
						);
				}
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Ajax Callback to validate addresses.
	 **/
	public function ajax_validate_address() {

		if ( empty( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), $this->ajax_nonce ) ) {
			wp_send_json_error( 'not-allowed' );
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'wrong-rights' );
		}


		if ( empty( $_GET['type'] ) || empty( $_GET['id'] ) || ! is_numeric( $_GET['id'] ) ) {
			wp_send_json_error( 'wrong-parameter' );
		}

		$allowed_types = array( 'shipping', 'billing' );
		$type = sanitize_text_field( wp_unslash( $_GET['type'] ) );
		$id = (int) wp_unslash( $_GET['id'] );

		if ( ! in_array( $type, $allowed_types, TRUE ) ) {
			wp_send_json_error( 'type-not-allowed' );
		}

		if ( ! class_exists( 'WC_Order' ) ) {
			wp_send_json_error( 'no-woocommerce' );
		}

		$order = new WC_Order( $id );
		$wc_address = $order->get_address( $type );

		$address = new WCAV_Address(
			$wc_address['address_1'],
			$wc_address['address_2'],
			$wc_address['postcode'],
			$wc_address['city'],
			'',
			$wc_address['country']
		);

		$is_valid = $this->validator->run( $address );
		$sanitized = $this->validator->get_sanitized_address();

		if ( method_exists( $sanitized, 'get_address' ) ) {
			$sanitized = $sanitized->get_address();
		}
		$data = array();
		$data['is_valid']  = $is_valid;
		$data['status']    = $this->validator->get_status();
		$data['sanitized'] = $sanitized;

		wp_send_json_success( $data );
	}
}
