<?php
/**
 * Plugin Name:       Live to Eat Food Core
 * Description:       Places, Woo-pons, events, jobs, ad spots and the submission forms for Live to Eat Food. Every submission waits in Tina's review queue until she approves it. Pairs with the Live to Eat Food block theme.
 * Version:           1.11.3
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Author:            Jeremy Anderson Tech
 * Author URI:        https://jeremyanderson.tech
 * License:           GPL-2.0-or-later
 * Text Domain:       ltef
 *
 * @package LTEF
 */

defined( 'ABSPATH' ) || exit;

define( 'LTEF_VERSION', '1.11.3' ); // keep in step with the header: browsers and Cloudflare cache the scripts by this
define( 'LTEF_FILE', __FILE__ );
define( 'LTEF_DIR', plugin_dir_path( __FILE__ ) );
define( 'LTEF_URL', plugin_dir_url( __FILE__ ) );

require_once LTEF_DIR . 'inc/helpers.php';
require_once LTEF_DIR . 'inc/post-types.php';
require_once LTEF_DIR . 'inc/meta.php';
require_once LTEF_DIR . 'inc/render.php';
require_once LTEF_DIR . 'inc/blocks.php';
require_once LTEF_DIR . 'inc/forms.php';
require_once LTEF_DIR . 'inc/admin.php';
require_once LTEF_DIR . 'inc/setup.php';
require_once LTEF_DIR . 'inc/money.php';
require_once LTEF_DIR . 'inc/email.php';

register_activation_hook(
	__FILE__,
	function () {
		ltef_register_post_types();
		flush_rewrite_rules();
	}
);
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
