<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              #
 * @since             1.0.0
 * @package           Woocommerce_Shuttle_Booking
 *
 * @wordpress-plugin
 * Plugin Name:       Woocommerce Shuttle Booking
 * Plugin URI:        #
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Woocommerce Shuttle Booking Team
 * Author URI:        #
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woocommerce-shuttle-booking
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PLUGIN_NAME_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-woocommerce-shuttle-booking-activator.php
 */
function activate_woocommerce_shuttle_booking() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-shuttle-booking-activator.php';
	Woocommerce_Shuttle_Booking_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-woocommerce-shuttle-booking-deactivator.php
 */
function deactivate_woocommerce_shuttle_booking() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-shuttle-booking-deactivator.php';
	Woocommerce_Shuttle_Booking_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_woocommerce_shuttle_booking' );
register_deactivation_hook( __FILE__, 'deactivate_woocommerce_shuttle_booking' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-woocommerce-shuttle-booking.php';

function run_woocommerce_shuttle_booking() {

	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		$plugin = new Woocommerce_Shuttle_Booking();
		$plugin->run();
	} else {
		if( is_admin() ) {
			as_woocommerce_activation_notice();
		}
	}

}

/**
 * Show notice message on admin plugin page.
 *
 * Callback function for admin_notices(action).
 *
 * @since  1.0.0
 * @access public
 */
function as_woocommerce_activation_notice() {
	?>
	<div class="error">
		<p>
			<?php echo '<strong> Woo Products Quantity Range Pricing </strong> requires <a target="_blank" href="https://wordpress.org/plugins/woocommerce/">Woocommerce</a> to be installed & activated!' ; ?>
		</p>
	</div>
	<?php
}


run_woocommerce_shuttle_booking();