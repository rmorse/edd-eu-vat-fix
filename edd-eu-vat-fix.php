<?php
/**
 * Plugin Name:       EDD EU VAT Fix
 * Plugin URI:        https://rm.codes
 * Description:       Helps to fix payments with errors due to issues with EDD and using the outdated Stripe plugin.
 * Version:           1.4.9
 * Author:            Ross Morsali
 * Author URI:        https://rm.codes
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       edd-eu-vat-fix
 * Domain Path:       /languages
 */

/**
 * The code that runs during plugin activation.
 */
/* function activate_edd_eu_vat_fix() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/core/class-activator.php';
	edd_eu_vat_fix\Activator::activate();
} */

/**
 * The code that runs during plugin deactivation.
 */
/* function deactivate_edd_eu_vat_fix() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/core/class-deactivator.php';
	edd_eu_vat_fix\Deactivator::deactivate();
} 

register_activation_hook( __FILE__, 'activate_edd_eu_vat_fix' );
register_deactivation_hook( __FILE__, 'deactivate_edd_eu_vat_fix' );*/

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-plugin.php';


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_edd_eu_vat_fix() {

	$plugin = new edd_eu_vat_fix();
	$plugin->run();

}
run_edd_eu_vat_fix();