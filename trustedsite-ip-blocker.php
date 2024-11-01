<?php
defined( 'ABSPATH' ) OR exit;

/**
 * ------------------------------------------------------------------------------------------------------------------
 * @package trustedsite-ip-blocker
 * @version 1.1.2
 * Plugin Name: TrustedSite IP Blocker
 * Plugin URI: https://www.trustedsite.com/
 * Description: TrustedSite
 * Author: TrustedSite
 * Version: 1.1.2
 * Author URI: https://www.trustedsite.com/
 * ------------------------------------------------------------------------------------------------------------------
 */

if(defined('WP_INSTALLING') && WP_INSTALLING){
    return;
}
define('TS_IP_BLOCKER_VERSION', '1.1.2');

add_action('activated_plugin','ts_ip_blocker_save_activation_error');
function ts_ip_blocker_save_activation_error(){
    update_option('ts_ip_blocker_plugin_error',  ob_get_contents());
}

require_once('lib/App.php');
register_activation_hook(__FILE__, 'TSIPBlocker\App::activate');
register_deactivation_hook(__FILE__, 'TSIPBlocker\App::deactivate');
register_uninstall_hook(__FILE__, 'TSIPBlocker\App::uninstall');

TSIPBlocker\App::install();

