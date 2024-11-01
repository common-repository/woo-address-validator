<?php
/**
 * The Woocommerce Admin handler
 */

/**
 * Class WCAV_WC_Admin
 */
class WCAV_WC_Admin {

	/**
	 * WCAV_WC_Admin constructor.
	 */
	public function __construct() {

		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_billing_address_status' ) );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'display_shipping_address_status' ) );
	}

	/**
	 * Display the status of the billing address of an order.
	 *
	 * @param $order
	 */
	public function display_billing_address_status( $order ) {

		$status = get_post_meta( $order->id, 'wcav_billing_status', TRUE );
		if ( ! $status ) {
			$status = __( 'Not checked.', 'woo-address-validator' );
		}

		$button_info = '';
		if ( 'valid' !== strtolower( $status ) ) {
			$button_info = array( 'billing', $order->id );
		}
		$this->render_backend_status( $status, $button_info );
	}

	/**
	 * Display the status of the shipping address of an order.
	 *
	 * @param $order
	 */
	public function display_shipping_address_status( $order ) {

		$meta_key = 'wcav_shipping_status';
		$shipping_address = $order->get_address( 'shipping' );
		$billing_address = $order->get_address( 'billing' );
		$diff = array_diff( $shipping_address, $billing_address );
		if ( empty( $diff ) ) {
			$meta_key = 'wcav_billing_status';
		}
		$status = get_post_meta( $order->id, $meta_key, TRUE );
		if ( ! $status ) {
			$status = __( 'Not checked.', 'woo-address-validator' );
		}
		$button_info = '';
		if ( 'valid' !== strtolower( $status ) ) {
			$button_info = array( 'shipping', $order->id );
		}
		$this->render_backend_status( $status, $button_info );
	}

	/**
	 * Render the status of an address in the admin interface.
	 *
	 * @param string $status
	 * @param mixed  $button_info
	 */
	public function render_backend_status( $status, $button_info ) {

		?>
		<p>
			<strong>
				<?php esc_html_e( 'Address status: ', 'woo-address-validator' ); ?>
			</strong><br>
			<span class="wcav-status">
				<?php echo esc_html( $status ); ?>
			</span>
		</p>


		<?php if ( is_array( $button_info) ) :
			wp_enqueue_script( 'wcav' );
			add_thickbox();
			?>
			<button
				class="button validate-address"
				data-id="<?php echo (int) $button_info[1] ; ?>"
				data-type="<?php echo esc_attr( $button_info[0] ); ?>"
			>
				<?php esc_html_e( 'Validate', 'woo-address-validator' ); ?>
			</button>
		<?php endif; ?>
		<div id="wcav-modal-wrapper" style="display:none">
			<div id="wcav-modal">
				<h2><?php esc_html_e( 'API Result', 'woo-address-validator' ); ?></h2>
				<p><?php
					esc_html_e( 'Address status: ', 'woo-address-validator' );
					?>
					<span id="wcav-address-status"></span>
				</p>
				<div id="wcav-sanitized-wrapper" style="display:none">
					<p>
						<strong>
							<?php echo esc_html_e( 'Sanitized Address:', 'woo-address-validator' ); ?>
						</strong>
					</p>
					<div id="wcav-sanitized"></div>
				</div>
			</div>
		</div>
		<?php
	}
}