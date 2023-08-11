<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 *
 * @link              https://broke.dev/brokestack
 * @since             1.0.0
 * @package           Broke_Components
 *
 * @wordpress-plugin
 * Plugin Name:       Broke Components
 * Plugin URI:        https://broke.dev/brokestack/components
 * Description:       Intuitive templating and component creation for WordPress, building upon the Lisa Templates foundation.
 * Version:           1.5.1
 * Author:            Broke.dev
 * Author URI:        https://broke.dev/brokestack
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       broke-components
 * Domain Path:       /languages
 * GitHub Plugin URI: domartisan/broke-components
 * GitHub Plugin URI: https://github.com/domartisan/broke-components
 */

function Broke_Components_deactivate() {
  deactivate_plugins( plugin_basename( __FILE__ ) );
}

function Broke_Components_dependency_admin_notice() {
  echo '<div class="updated"><p><strong>Broke Components</strong> requires the plugin <a href="https://wordpress.org/plugins/timber-library/" target="_blank">Timber</a> to be activated; the plug-in has been <strong>deactivated</strong>.</p></div>';
  if ( isset( $_GET['activate'] ) )
    unset( $_GET['activate'] );
}

function Broke_Components_check_dependencies() {
  if ( ! class_exists( '\Timber\Timber' ) ) {
    add_action( 'admin_init', 'Broke_Components_deactivate' );
    add_action( 'admin_notices', 'Broke_Components_dependency_admin_notice' );
  } else {
  	/**
  	 * The core plugin class that is used to define internationalization,
  	 * admin-specific hooks, and public-facing site hooks.
  	 */
  	require plugin_dir_path( __FILE__ ) . 'includes/class-broke.php';

  	/**
  	 * Begins execution of the plugin.
  	 *
  	 * Since everything within the plugin is registered via hooks,
  	 * then kicking off the plugin from this point in the file does
  	 * not affect the page life cycle.
  	 *
  	 * @since    1.0.0
  	 */
  	function run_Broke_Components() {

  		$plugin = new Broke_Components();
  		$plugin->run();

  	}
  	run_Broke_Components();
  }
}
add_action( 'plugins_loaded', 'Broke_Components_check_dependencies', 2 );
