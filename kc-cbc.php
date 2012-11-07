<?php

/**
 * @package KC_CBC
 * @version 0.1.1
 */


/*
Plugin name: KC Content by Country
Plugin URI: http://kucrut.org/
Description: Filter contents based on visitor's country by using custom taxonomy.
Version: 0.1.1
Author: Dzikri Aziz
Author URI: http://kucrut.org/
License: GPL v2
Text Domain: kc-cbc
*/

class kcCBC {
	const version = '0.1';
	private static $data = array();


	public static function setup() {
		$dir = self::$data['dir'] = dirname( __FILE__ ) . '/kc-cbc-inc';
		require_once "{$dir}/helpers.php";
		if ( !class_exists('GeoIP') )
			require_once "{$dir}/geoip.inc";

		add_action( 'init', array(__CLASS__, 'init'), 19 );
	}


	public static function init() {
		$post_types = apply_filters( 'kc_cbc_post_types', array('post') );
		if ( !is_array($post_types) || empty($post_types) )
			return false;

		self::$data['post_types'] = $post_types;
		self::register_taxonomy();

		# For dev.
		if ( !defined('KC_CBC_DEBUG') )
			define( 'KC_CBC_DEBUG', false );
		if ( !defined('KC_CBC_IP') )
			define( 'KC_CBC_IP', '180.246.211.195' );

		if ( !is_admin() )
			self::init_front();
	}


	public static function register_taxonomy() {
		register_taxonomy( 'kc-cbc', self::$data['post_types'], array(
			'labels'       => array(
				'name'               => __('Countries', 'kc-cbc'),
				'singular_name'      => __('Country', 'kc-cbc'),
				'add_new_item'       => __('Add New Country', 'kc-cbc'),
				'edit_item'          => __('Edit Country', 'kc-cbc'),
				'new_item'           => __('New Country', 'kc-cbc'),
				'view_item'          => __('View Country', 'kc-cbc'),
				'search_items'       => __('Search Countries', 'kc-cbc'),
				'not_found'          => __('No country found', 'kc-cbc'),
				'not_found_in_trash' => __('No country found in trash', 'kc-cbc')
			),
			'public'       => true,
			'hierarchical' => false,
			'rewrite'      => false
		) );

		self::create_terms();
	}


	public static function create_terms( $force = false ) {
		$GeoIP = get_class_vars('GeoIP');
		$country_names = array_slice( $GeoIP['GEOIP_COUNTRY_NAMES'], 1 );
		$country_codes = array_slice( $GeoIP['GEOIP_COUNTRY_CODES'], 1 );

		$country_terms = get_terms( 'kc-cbc', array('hide_empty' => false) );
		if ( (count($country_terms) !== count($country_names)) || $force ) {
			foreach ( $country_names as $idx => $name )
				wp_insert_term( $name, 'kc-cbc', array( 'slug' => strtolower($country_codes[$idx]) ) );
		}
	}


	public static function init_front() {
		$ip = KC_CBC_DEBUG ? KC_CBC_IP : $_SERVER['REMOTE_ADDR'];

		$_gi = geoip_open( self::$data['dir'] . '/GeoIP.dat',  GEOIP_STANDARD );
		$country = self::$data['country'] = get_term_by( 'slug', strtolower( geoip_country_code_by_addr( $_gi, $ip ) ), 'kc-cbc' );
		geoip_close( $_gi );

		if ( $country && !is_singular() && !is_front_page() )
			add_action( 'parse_query', array(__CLASS__, 'modify_query') );
	}


	public static function modify_query( $query ) {
		if ( !$query->is_main_query() )
			return;

		if ( !defined('KC_CBC_INCLUSIVE') )
			define( 'KC_CBC_INCLUSIVE', true );

		$qv =& $query->query_vars;
		if ( !isset($qv['tax_query']) )
			$qv['tax_query'] = array();

		$qv['tax_query'][] = array(
			'taxonomy' => 'kc-cbc',
			'field'    => 'slug',
			'terms'    => self::$data['country']->slug,
			'operator' => KC_CBC_INCLUSIVE ? 'IN' : 'NOT IN'
		);
	}


	public static function get_data() {
		$data = self::$data;
		if ( !func_num_args() )
			return $data;

		$args = func_get_args();
		return kc_array_multi_get_value( $data, $args );
	}
}
add_action( 'plugins_loaded', array('kcCBC', 'setup') );
