<?php
/**
 * Plugin Name: WP Performance Tester
 * Description: Test the performance of your WordPress site.
 * Version: 1.0
 * Author: Kailan Wyatt
 * Author URI: https://github.com/kailanwyatt
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-performance-tester
 * Domain Path: /languages
 *
 * @package WP_Performance_Tester
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main WP_Performance_Tester Class.
 *
 * This plugin will test the performance of your WordPress site by measuring the time it takes to load the site.
 * It will run a cron job that will loop through your active plugins, deactivate them one by one, and measure the time it takes to load the site.
 *
 * @class WP_Performance_Tester
 */
class WP_Performance_Tester {
	/**
	 * The single instance of the class.
	 *
	 * @var WP_Performance_Tester
	 */
	protected static $instance = null;

	/**
	 * Minimum required plugins.
	 *
	 * @var array
	 */
	public $minimum_required_plugins = array();

	/**
	 * Main WP_Performance_Tester Instance.
	 *
	 * Ensures only one instance of WP_Performance_Tester is loaded or can be loaded.
	 *
	 * @static
	 * @return WP_Performance_Tester - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * WP_Performance_Tester Constructor.
	 *
	 * @param array $minimum_required_plugins Minimum required plugins.
	 */
	public function __construct( $minimum_required_plugins = array() ) {
		$this->minimum_required_plugins = $minimum_required_plugins;
		$this->init_hooks();
	}

	/**
	 * Hook into actions and filters.
	 */
	private function init_hooks() {
		add_action( 'wp_ajax_nopriv_wp_performance_tester', array( $this, 'ajax_callback' ) );
		add_filter( 'option_active_plugins', array( $this, 'filter_active_plugins_for_specific_ajax' ) );
	}

	/**
	 * AJAX callback.
	 */
	public function ajax_callback() {
		$test_time            = date( 'Y-m-d H:i:s' );
		$start_time           = isset( $_REQUEST['start_time'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['start_time'] ) ) : '';
		$plugin_to_deactivate = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) : '';
		$security             = isset( $_REQUEST['security'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['security'] ) ) : '';

		if ( ! wp_verify_nonce( $security, 'wp_performance_tester' ) ) {
			wp_send_json_error( 'Invalid security token' );
		}

		$use_logging = isset( $_REQUEST['use_logging'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['use_logging'] ) ) : false;

		$end_time     = microtime( true );
		$elapsed_time = $end_time - $start_time;

		// Convert elapsed time to milliseconds.
		$elapsed_time_ms = $elapsed_time * 1000;
		$formatted_time  = number_format( $elapsed_time, 3 ) . 's/' . number_format( $elapsed_time_ms, 0 ) . 'ms';

		if ( $use_logging ) {
			error_log( $test_time . ' :: ' . $plugin_to_deactivate . ' - ' . $formatted_time );
		} else {
			do_action( 'wp_performance_tester', $test_time, $plugin_to_deactivate, $formatted_time );
		}

		wp_send_json_success(
			array(
				'time'   => $formatted_time,
				'plugin' => $plugin_to_deactivate,
			)
		);
	}

	/**
	 * Filter active plugins for specific AJAX requests.
	 *
	 * @param array $active_plugins Active plugins.
	 * @return array
	 */
	public function filter_active_plugins_for_specific_ajax( $active_plugins ) {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return $active_plugins;
		}

		// Verify nonce.
		if ( ! isset( $_REQUEST['security'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['security'] ), 'wp_performance_tester' ) ) ) {
			return $active_plugins;
		}

		// Verify action.
		if ( ! isset( $_REQUEST['action'] ) || 'wp_performance_tester' !== sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) ) {
			return $active_plugins;
		}
		// Plugin to deactivate temporarily.
		$temp_plugin = ! empty( $_REQUEST['plugin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) : '';

		// If the request is an AJAX request and the action is 'wp_performance_tester' and the temp_plugin is not empty, then deactivate the temp_plugin temporarily.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_REQUEST['action'] ) && 'wp_performance_tester' === sanitize_text_field( $_REQUEST['action'] ) && ! empty( $temp_plugin ) ) {
			$active_plugins = $this->deactivate_plugin( $active_plugins, $temp_plugin );
		}

		return $active_plugins;
	}

	/**
	 * Deactivate a plugin temporarily.
	 *
	 * @param array $active_plugins Active plugins.
	 * @param string $plugin Plugin to deactivate.
	 * @return array
	 */
	public function deactivate_plugin( $active_plugins, $plugin ) {
		// Filter the active plugins to keep only the specified ones.
		$active_plugins = array_filter(
			$active_plugins,
			function ( $active_plugin ) use ( $plugin ) {
				return $active_plugin !== $plugin;
			}
		);
		return $active_plugins;
	}

	/**
	 * WP CLI command to run performance tests.
	 *
	 * @param array $args CLI Args.
	 * @param array $assoc_args CLI Assoc Args.
	 */
	public function cli_command( $args = array(), $assoc_args = array() ) {
		// Check if a single plugin test is requested.
		if ( ! empty( $assoc_args['plugin'] ) ) {
			$this->test_single_plugin( $assoc_args['plugin'], $assoc_args['use_logging'] );
			return;
		}

		// Get all active plugins.
		$active_plugins = get_option( 'active_plugins' );

		$use_logging = isset( $assoc_args['use_logging'] ) ? $assoc_args['use_logging'] : false;

		$minimum_required_plugins       = isset( $assoc_args['minimum_required_plugins'] ) ? explode( ',', $assoc_args['minimum_required_plugins'] ) : array();
		$this->minimum_required_plugins = $minimum_required_plugins;

		// Array to store plugin times.
		$plugin_times = array();

		// Loop through each plugin.
		foreach ( $active_plugins as $plugin ) {
			// Never deactivate a plugin that is required.
			if ( in_array( $plugin, $this->minimum_required_plugins, true ) ) {
				continue;
			}

			$nonce      = wp_create_nonce( 'wp_performance_tester' );
			$start_time = microtime( true );
			// Perform an AJAX request to deactivate the plugin temporarily.
			$response = wp_remote_post(
				admin_url( 'admin-ajax.php' ),
				array(
					'body' => array(
						'action'      => 'wp_performance_tester',
						'plugin'      => $plugin,
						'use_logging' => $use_logging,
						'start_time'  => $start_time,
						'security'    => $nonce,
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				WP_CLI::error( $response->get_error_message() );
			} else {
				$response_body = wp_remote_retrieve_body( $response );
				$response_data = json_decode( $response_body, true );
				if ( isset( $response_data['success'] ) && $response_data['success'] ) {
					$plugin_times[ $plugin ] = floatval( $response_data['data']['time'] );
				}
			}
		}

		// Find the top offending plugin (lowest time).
		if ( ! empty( $plugin_times ) ) {
			asort( $plugin_times );
			$top_offending_plugin = key( $plugin_times );
			$top_time             = $plugin_times[ $top_offending_plugin ];
			WP_CLI::log( "Top offending plugin: $top_offending_plugin with time: $top_time" );
			if ( $use_logging ) {
				error_log( "Top offending plugin: $top_offending_plugin with time: $top_time" );
			}
		}
	}

	/**
	 * Test the performance impact of a single plugin.
	 *
	 * @param string $plugin
	 * @param bool $use_logging
	 */
	private function test_single_plugin( $plugin, $use_logging = false ) {
		$nonce = wp_create_nonce( 'wp_performance_tester' );

		$start_time = microtime( true );
		// Perform an AJAX request to deactivate the plugin temporarily.
		$response = wp_remote_post(
			admin_url( 'admin-ajax.php' ),
			array(
				'body' => array(
					'action'      => 'wp_performance_tester',
					'plugin'      => $plugin,
					'use_logging' => $use_logging,
					'start_time'  => $start_time,
					'security'    => $nonce,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			WP_CLI::error( $response->get_error_message() );
		} else {
			$response_body = wp_remote_retrieve_body( $response );
			$response_data = json_decode( $response_body, true );
			if ( isset( $response_data['success'] ) && $response_data['success'] ) {
				$time   = floatval( $response_data['data']['time'] );
				$plugin = $response_data['data']['plugin'];
				WP_CLI::success( "$plugin - $time" );
				if ( $use_logging ) {
					error_log( "$plugin - $time" );
				}
			}
		}
	}
}

/**
 * Initialize the plugin.
 */
function wp_performance_tester() {
	return WP_Performance_Tester::instance();
}

// Initialize the plugin.
wp_performance_tester();

/**
 * Register CLI command.
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'performance_test', array( 'WP_Performance_Tester', 'cli_command' ) );
}
