<?php
/**
 * Plugin Name: Claude for WP
 * Description: Integra Claude (Anthropic) en el admin de WordPress. Chat, generación de contenido y código Elementor.
 * Version: 1.0.0
 * Author: Tu nombre
 * Text Domain: claude-for-wp
 */

defined( 'ABSPATH' ) || exit;

define( 'CFW_VERSION', '1.0.0' );
define( 'CFW_PATH', plugin_dir_path( __FILE__ ) );
define( 'CFW_URL', plugin_dir_url( __FILE__ ) );

require_once CFW_PATH . 'includes/class-cfw-settings.php';
require_once CFW_PATH . 'includes/class-cfw-api.php';
require_once CFW_PATH . 'includes/class-cfw-admin.php';
require_once CFW_PATH . 'includes/class-cfw-ajax.php';

function cfw_init(): void {
    CFW_Settings::init();
    CFW_Admin::init();
    CFW_Ajax::init();
}
add_action( 'plugins_loaded', 'cfw_init' );
