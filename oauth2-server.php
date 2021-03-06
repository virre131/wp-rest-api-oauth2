<?php
/**
 * Plugin Name: WP REST API - OAuth 2.0 Server
 * Plugin URI: https://github.com/apkoponen/wp-rest-api-oauth2
 * Description: OAuth2 Flow for providing authorization access to WordPress REST API.
 * Version: 1.0.1
 * Author: justingreerbbi, ap.koponen
 * Requires at least: 4.4
 * License: GPL3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Load helpers
require_once( dirname( __FILE__ ) . '/lib/helpers/class-oa2-header-helper.php' );
require_once( dirname( __FILE__ ) . '/lib/helpers/class-oa2-error-helper.php' );
require_once( dirname( __FILE__ ) . '/lib/helpers/class-oa2-scope-helper.php' );

// Load core classes
require_once( dirname( __FILE__ ) . '/lib/storage/class-wp-rest-oauth-client.php' );
require_once( dirname( __FILE__ ) . '/lib/storage/class-oa2-client.php' );
require_once( dirname( __FILE__ ) . '/lib/storage/class-oa2-token.php' );
require_once( dirname( __FILE__ ) . '/lib/storage/class-oa2-access-token.php' );
require_once( dirname( __FILE__ ) . '/lib/storage/class-oa2-refresh-token.php' );
require_once( dirname( __FILE__ ) . '/lib/storage/class-oa2-authorization-code.php' );

// Load UI
require_once( dirname( __FILE__ ) . '/lib/class-oa2-ui.php' );

// Initiate admin
require_once( dirname( __FILE__ ) . '/admin.php' );

// Require
require_once( dirname( __FILE__ ) . '/lib/class-oa2-authorize-controller.php' );
require_once( dirname( __FILE__ ) . '/lib/class-oa2-token-controller.php' );
require_once( dirname( __FILE__ ) . '/lib/class-oa2-storage-controller.php' );

// Initiate the server
add_action( 'rest_api_init', array( 'OA2_Server', 'register_routes' ) );
add_action( 'init', array( 'OA2_Server', 'register_storage' ) );
add_filter( 'rest_index', array( 'OA2_Server', 'add_routes_to_index' ) );
add_action( 'plugins_loaded', array( 'OA2_Server', 'init_autheticator' ) );
add_action( 'plugins_loaded', array( 'OA2_Server', 'load_textdomain' ) );
add_action( 'init', array( 'OA2_Server', 'load_authorize_ui' ) );

/**
 * OAuth2 Rest Server Main Class.
 *
 * This class is used to
 */
class OA2_Server {

  public static $authenticator = null;

  /**
   * Registers routes needed for the OAuth2 Server
   *
   */
  static function register_routes() {
	// Registers the authorize endpoint
	register_rest_route( 'oauth2/v1', '/authorize', array(
		'methods'	 => 'GET',
		'callback'	 => array( 'OA2_Authorize_Controller', 'validate' ),
	) );

	// Registers the token endpoint
	register_rest_route( 'oauth2/v1', '/token', array(
		'methods'	 => 'POST',
		'callback'	 => array( 'OA2_Token_Controller', 'validate' ),
	) );
  }

  /* Register routes to authentication on rest index
   *
   * @param object $response_object WP_REST_Response Object
   * @return object Filtered WP_REST_Response object
   */
  static function add_routes_to_index( $response_object ) {
	if ( empty( $response_object->data[ 'authentication' ] ) ) {
	  $response_object->data[ 'authentication' ] = array();
	}

	$response_object->data[ 'authentication' ][ 'oauth2' ] = array(
		'authorize'	 => get_rest_url(null, '/oauth2/v1/authorize' ),
		'token'	 => get_rest_url(null, '/oauth2/v1/token' ),
		'version'	 => '0.1',
	);
	return $response_object;
  }

  /**
   * Register the CPTs needed for storage.
   */
  static function register_storage() {
	register_post_type( 'json_consumer', array(
		'labels' => array(
			'name' => __( 'Consumers', 'wp_rest_oauth2' ),
			'singular_name' => __( 'Consumer', 'wp_rest_oauth2' ),
		),
		'public' => false,
		'hierarchical' => false,
		'rewrite' => false,
		'delete_with_user' => true,
		'query_var' => false,
	) );

	register_post_type( 'oauth2_access_token', array(
		'labels' => array(
			'name' => __( 'Access tokens', 'wp_rest_oauth2' ),
			'singular_name' => __( 'Access token', 'wp_rest_oauth2' ),
		),
		'public' => false,
		'hierarchical' => false,
		'rewrite' => false,
		'delete_with_user' => true,
		'query_var' => false,
	) );

	register_post_type( 'oauth2_refresh_token', array(
		'labels' => array(
			'name' => __( 'Refresh tokens', 'wp_rest_oauth2' ),
			'singular_name' => __( 'Refresh token', 'wp_rest_oauth2' ),
		),
		'public' => false,
		'hierarchical' => false,
		'rewrite' => false,
		'delete_with_user' => true,
		'query_var' => false,
	) );
  }

  /**
   * Setup the authenticator.
   */
  static function init_autheticator() {
	include_once( dirname( __FILE__ ) . '/lib/class-oa2-authenticator.php' );

	self::$authenticator = new OA2_Authenticator();
  }

  /**
   * Register the authorization page
   */
  static function load_authorize_ui() {
	$authorize_ui = new OA2_UI();
	$authorize_ui->register_hooks();
  }

  /**
   * Load textdomain
   */
  static function load_textdomain() {
	load_plugin_textdomain( 'wp_rest_oauth2', false, dirname( plugin_basename(__FILE__) ) . '/languages/' );
  }
}
