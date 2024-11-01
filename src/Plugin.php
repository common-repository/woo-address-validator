<?php
/**
 * The main plugin class
 *
 * @package WCAV
 */

/**
 * Class WCAV_Plugin
 */
class WCAV_Plugin {

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
	 * The api key.
	 *
	 * @var string
	 */
	private $api_key = '';

	/**
	 * The WCAV_API.
	 *
	 * @var WCAV_API | NULL
	 */
	private $api = NULL;

	/**
	 * The WCAV_Validator.
	 *
	 * @var WCAV_Validator | NULL
	 */
 	private $validator = NULL;
       
   /**
    * Allow INVALID addresses option.
    *
    * @var bool
    */
   private $allow_invalidaddr = 0;

	/**
	 * Setup the plugin.
	 */
	public function setup() {

		$this->plugin_path = dirname( __FILE__ );
		$this->plugin_url = plugins_url( '/', $this->plugin_path );

		$options = get_option( 'wcav', array() );
		if ( ! empty( $options['api-key'] )  ) {
			$this->api_key = $options['api-key'];
		}
		if ( ! empty( $options['allow-invalidaddr'] )  ) {
			$this->allow_invalidaddr = $options['allow-invalidaddr'];
		}

		require_once( dirname( __FILE__ ) . '/API/Address.php' );
		require_once( dirname( __FILE__ ) . '/API/API.php' );
		require_once( dirname( __FILE__ ) . '/API/Validator.php' );
		$this->api = new WCAV_API( $this->api_key );
		$this->validator = new WCAV_Validator( $this->api );

		if ( is_admin() ) {
			require_once( dirname( __FILE__ ) . '/Admin.php' );
			require_once( dirname( __FILE__ ) . '/WC/WCAV_WC_Admin.php' );
			$admin = new WCAV_Admin();
			$admin->setup( $this->plugin_path, $this->plugin_url, $this->validator );
			new WCAV_WC_Admin();
		}

		require_once( dirname( __FILE__ ) . '/WC/WC.php' );
		$wc = new WCAV_WC();
		$wc->setup( $this->plugin_path, $this->plugin_url, $this->validator, $this->allow_invalidaddr );
	}
}
