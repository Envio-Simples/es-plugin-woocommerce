<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/srgoogle23/es-plugin-woo
 * @since      1.0.0
 *
 * @package    Es_Plugin_Woo
 * @subpackage Es_Plugin_Woo/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Es_Plugin_Woo
 * @subpackage Es_Plugin_Woo/includes
 * @author     srgoogle23 <contato@leonardoliveira.com>
 */
class Es_Plugin_Woo {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Es_Plugin_Woo_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'ES_PLUGIN_WOO_VERSION' ) ) {
			$this->version = ES_PLUGIN_WOO_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'es-plugin-woo';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_everywhere_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Es_Plugin_Woo_Loader. Orchestrates the hooks of the plugin.
	 * - Es_Plugin_Woo_i18n. Defines internationalization functionality.
	 * - Es_Plugin_Woo_Admin. Defines all hooks for the admin area.
	 * - Es_Plugin_Woo_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-es-plugin-woo-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-es-plugin-woo-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-es-plugin-woo-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-es-plugin-woo-public.php';

		/**
		 * The class responsible for all functionalits in orders
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-es-plugin-woo-label.php';

		/**
		 * The class responsible for all curls
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-es-plugin-woo-api.php';

		/**
		 * The main class 
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-es-plugin-woo-main.php';

		/**
		 * The main class 
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-es-plugin-woo-simples.php';

		$this->loader = new Es_Plugin_Woo_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Es_Plugin_Woo_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Es_Plugin_Woo_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_everywhere_hooks() {
		add_action('woocommerce_shipping_init','woocommerce_enviosimples_init');
		$label = new Es_Plugin_Woo_Label();
		$main = new Es_Plugin_Woo_main();
		$this->loader->add_action( 'woocommerce_order_status_processing', $main, 'isw_woo_update_ticket', 10, 1 );
		$this->loader->add_action( 'woocommerce_order_status_completed', $main, 'isw_woo_update_ticket', 10, 1 );
		$this->loader->add_action( 'manage_shop_order_posts_custom_column', $main, 'isw_column_ticket_values', 2 );
		$this->loader->add_filter( 'woocommerce_shipping_methods', $main, 'add_woocommerce_enviosimples');
		$this->loader->add_action( 'wp_enqueue_scripts', $main, 'enviosimples_enqueue_user_scripts');
		$this->loader->add_action( 'admin_enqueue_scripts', $main, 'enviosimples_enqueue_user_scripts');
		$this->loader->add_action( 'woocommerce_after_add_to_cart_form', $main, 'enviosimples_shipping_forecast_on_product_page',50);
		$this->loader->add_filter( 'manage_edit-shop_order_columns', $main, 'isw_column_ticket');
		$this->loader->add_action( 'woocommerce_order_status_processing', $label, 'enviosimples_order_processing' );
	}
	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Es_Plugin_Woo_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Es_Plugin_Woo_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Es_Plugin_Woo_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}