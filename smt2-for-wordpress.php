<?php
/**
* Plugin Name: SMT2 for WordPress
* Description: An adaptation of SMT2 for WordPress.
* Version: 1.2
* Author: Sorin Coza
* Author URI: http://sorincoza.com
* Plugin URI: https://github.com/sorincoza/SMT2-for-WordPress
*
* Original code: https://code.google.com/p/smt2/
* GitHub Plugin URI: https://github.com/sorincoza/SMT2-for-WordPress
*/




// before anything else, we need some constants:
define( 'SMT2WP_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );


// include the shared functions
include_once 'shared-functions.php';



// include options page class and instantiate
if ( is_admin() ){
	include 'options-page-lib/class.php';
	new WordPress_Plugin_Template_Settings( __FILE__ );
}








// then bussiness as usual - actions and hooks:
add_action( 'wp_enqueue_scripts', 'smt2wp_scripts', 1000 );
add_action( 'admin_bar_menu', 'smt2wp_admin_bar_link', 999 );
add_action( 'init', 'smt2wp_init' );
add_action( 'admin_notices', 'smt2wp_show_error__cahe_dir' );
register_activation_hook( __FILE__, 'smt2wp_plugin_activate' );
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ) , 'smt2wp_add_list_links' );








// the functions:


function smt2wp_plugin_activate() {

	$cache_dir = get_smt2wp_cache_path();

	// if cache folder does not exist, attempt to create it:
	if ( !file_exists( $cache_dir )  ){
	  if( !mkdir( $cache_dir, 0777, true ) ){
	  	// do something if the folder failed to create
	  }
	}
    
}



function smt2wp_show_error__cahe_dir(){
	// check to see if cache folder is in place:
	if ( file_exists( get_smt2wp_cache_path() )  ){
		return;	
	}

	// if we got this far, then we don't have the cache folder
	$cache_dir = get_smt2wp_cache_path();
	$msg = '<strong>SMT2 cache folder is missing!</strong><br>';
	$msg .= 'The plugin probably attempted to create it, but failed, so you\'ll need to do it manually.<br>';
	$msg .= 'The folder should be named <b><i>' . basename( $cache_dir ) . '</i></b>, and its full path should be: <i>' . $cache_dir . '</i><br>';
	$msg .= '<br>If you deleted the folder on purpose,<br> then you should know that SMT will not give you the best possible experience without it, so it is recommended to create that folder again.';
	echo '<div class="error" style="padding:20px">' . $msg . '</div>';
}




function smt2wp_scripts(){

	wp_enqueue_script( 'smt2-main-script', SMT2WP_PLUGIN_DIR_URL . 'core/js/smt2e.min.js', array(), '', true );
	
	// pass the init options to script
	wp_localize_script( 
		'smt2-main-script',
		'smt2_init_options',
        smt2wp_get_init_options()
    );

}



function smt2wp_admin_bar_link( $wp_admin_bar ) {
	$args = array(
		'id'    => 'smt2_link_to_admin',
		'title' => 'SMT2 Admin',
		'href'  => SMT2WP_PLUGIN_DIR_URL . 'admin',
	);
	$wp_admin_bar->add_node( $args );	

	$args = array(
		'id'    => 'smt2_link_to_admin__child',
		'title' => 'SMT2 Dashboard',
		'href'  => SMT2WP_PLUGIN_DIR_URL . 'admin',
		'parent'=> 'smt2_link_to_admin'
	);
	$wp_admin_bar->add_node( $args );

	$args = array(
		'id'    => 'smt2_link_to_settings',
		'title' => 'SMT2 Settings',
		'href'  => 'options-general.php?page=smt2wp_plugin_settings',
		'parent'=> 'smt2_link_to_admin'
	);
	$wp_admin_bar->add_node( $args );
}


function smt2wp_get_init_options(){
	$res = array();
	$prefix = 'smt2wp_';

	$keys = array(
		'post_interval' => 30,
		'fps' => 24,
		'rec_time' => 3600,

		'disabled' => 0,
		'cont_recording' => true,
		'warn_text' => '',
		'cookie_days' => 365
	);

	foreach ( $keys as $key => $default) {
		// convert to camelCase	:
		$parts = explode( '_', $key );
		for ( $i=count($parts); $i-->1; ){   $parts[$i] = ucfirst( $parts[$i] );   }
		$key_cc = implode( '', $parts );
		

		// get option:
		$res[ $key_cc ] = get_option( $prefix . $key, $default );

		// sanitize values:
		if ( $key !== 'disabled'  &&  $key !== 'cont_recording'  &&  $key !== 'warn_text' ){
			if ( empty( $res[ $key_cc ] ) ){
				$res[ $key_cc ] = $default;
			}
		}

	}


	// finally, don't let the tracking path to chance
	$res[ 'trackingServer' ] = SMT2WP_PLUGIN_DIR_URL;

	return $res;

}




function smt2wp_init() {

	// init updater:
	include_once 'updater.php';

	define( 'WP_GITHUB_FORCE_UPDATE', false );
	define( 'GITHUB_USERNAME', 'sorincoza' );
	define( 'GITHUB_APP_NAME', 'smt2-for-wordpress');

	if ( is_admin() ) { // note the use of is_admin() to double check that this is happening in the admin

		// get proper directory name:
		$pieces = explode( '/', SMT2WP_PLUGIN_DIR_URL );
		$p_len = count($pieces);
		$proper_folder_name = ( !empty($pieces[ $p_len - 1 ]) )   ?   $pieces[ $p_len - 1 ]   :   $pieces[ $p_len - 2 ] ;


		// configuration:
		$config = array(
			'slug' => plugin_basename( __FILE__ ),
			'proper_folder_name' => basename( get_smt2wp_base_path() ),
			'api_url' => 'https://api.github.com/repos/'. GITHUB_USERNAME .'/' . GITHUB_APP_NAME,
			'raw_url' => 'https://raw.github.com/' . GITHUB_USERNAME .'/' . GITHUB_APP_NAME . '/master',
			'github_url' => 'https://github.com/' . GITHUB_USERNAME .'/' . GITHUB_APP_NAME,
			'zip_url' => 'https://github.com/' . GITHUB_USERNAME .'/' . GITHUB_APP_NAME . '/zipball/master',
			'sslverify' => true,
			'requires' => '4.0',
			'tested' => '4.3',
			'readme' => 'README.md',
			'access_token' => '',
		);

		new WP_GitHub_Updater( $config );

	}

}


function smt2wp_add_list_links( $links ) {

	$settings_link = '<br><a style="opacity:0.9;color:#7ad03a" href="'. SMT2WP_PLUGIN_DIR_URL . 'admin/sys/install.php">Install</a>';
	array_push( $links, $settings_link );

	$settings_link = '<a style="opacity:0.5" class="delete" href="'. SMT2WP_PLUGIN_DIR_URL . 'admin/sys/uninstall.php">Uninstall</a>';
	array_push( $links, $settings_link );
	
	return $links;
}

