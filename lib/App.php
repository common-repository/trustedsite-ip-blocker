<?php
namespace TSIPBlocker;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once('Util.php');
require_once('Schema.php');
require_once('AdminApi.php');
require_once('AdminPost.php');
require_once('Settings.php');
require_once('ScheduledJob.php');

class  App
{
    public static function activate()
    {
        Settings.reset();
        Schema::createTables();
        ScheduledJob::activate();
    }

    public static function install()
    {
        add_action( 'ts_ip_blocker_hourly_jobs', 'TSIPBlocker\ScheduledJob::hourly' );

        add_action('admin_menu', 'TSIPBlocker\App::adminMenus');
        add_action('template_redirect', 'TSIPBlocker\Hit::log', 1);        
        add_action('admin_enqueue_scripts', 'TSIPBlocker\App::scripts');

        //Admin API
        add_action('wp_ajax_ts_ip_blocker_get_hits', 'TSIPBlocker\AdminApi::getHits');
        add_action('wp_ajax_ts_ip_blocker_get_rules', 'TSIPBlocker\AdminApi::getRules');

        //For overview charts
        add_action('wp_ajax_ts_ip_blocker_get_daily_hits_count', 'TSIPBlocker\AdminApi::getDailyHitsCount');
        add_action('wp_ajax_ts_ip_blocker_get_hits_count_per_group', 'TSIPBlocker\AdminApi::getHitsCountPerGroup');
        add_action('wp_ajax_ts_ip_blocker_get_hits_count_per_referer', 'TSIPBlocker\AdminApi::getHitsCountPerReferer');
        add_action('wp_ajax_ts_ip_blocker_get_hits_count_per_ip', 'TSIPBlocker\AdminApi::getHitsCountPerIp');
        add_action('wp_ajax_ts_ip_blocker_get_rules_block_counts', 'TSIPBlocker\AdminApi::getRulesBlockCounts');

        add_action('wp_ajax_ts_ip_blocker_create_rule', 'TSIPBlocker\AdminApi::createRule');
        add_action('wp_ajax_ts_ip_blocker_delete_rule', 'TSIPBlocker\AdminApi::deleteRule');

        //Reset API
        add_action('wp_ajax_ts_ip_blocker_delete_all_hits', 'TSIPBlocker\AdminApi::deleteAllHits');
        add_action('wp_ajax_ts_ip_blocker_delete_all_rules', 'TSIPBlocker\AdminApi::deleteAllRules');
        add_action('wp_ajax_ts_ip_blocker_reset_app', 'TSIPBlocker\AdminApi::resetApp');

        //Admin Post
        add_action( 'admin_post_ts_ip_blocker_delete_all_hits', 'TSIPBlocker\AdminPost::deleteAllHits' );
        add_action( 'admin_post_ts_ip_blocker_delete_all_rules', 'TSIPBlocker\AdminPost::deleteAllRules' );
        add_action( 'admin_post_ts_ip_blocker_reset_app', 'TSIPBlocker\AdminPost::resetApp' );

        add_action( 'admin_post_ts_ip_blocker_update_general_settings', 'TSIPBlocker\AdminPost::updateGeneralSettings' );

        add_action( 'admin_post_ts_ip_blocker_create_rule', 'TSIPBlocker\AdminPost::createRule' );
        add_action( 'admin_post_ts_ip_blocker_delete_rule', 'TSIPBlocker\AdminPost::deleteRule' );
        add_action( 'admin_post_ts_ip_blocker_block_ip', 'TSIPBlocker\AdminPost::blockIp' );
        

        //Detect Notices
        if ( array_key_exists('tsipb_success', $_GET) && isset( $_GET['tsipb_success'] ) ) {
            App::success_notice();
        }
        if ( array_key_exists('tsipb_info', $_GET) && isset( $_GET['tsipb_info'] ) ) {
            App::info_notice();
        }
        if ( array_key_exists('tsipb_error', $_GET) && isset( $_GET['tsipb_error'] ) ) {
            App::error_notice();
        }
    }

    public static function success_notice(){        
        add_action( 'admin_notices', function() {
            printf('<div class="notice notice-success is-dismissible"><p>' . $_GET['tsipb_success']  . '</p></div>');
        });
    }

    public static function info_notice(){        
        add_action( 'admin_notices', function() {
            printf('<div class="notice notice-info is-dismissible"><p>' . $_GET['tsipb_info']  . '</p></div>');
        });
    }

    public static function error_notice(){        
        add_action( 'admin_notices', function() {
            printf('<div class="notice notice-error is-dismissible"><p>' . $_GET['tsipb_error']  . '</p></div>');
        });
    }

    public static function scripts($hook) {
        if (strpos($hook, "ts-ip-blocker") !== false) {
            wp_enqueue_script('ts-ip-blocker-app-script', plugins_url('../js/app.js', __FILE__), array('jquery'));            
            wp_enqueue_style('ts-ip-blocker-app-font-awesome-css', plugins_url('../css/font-awesome.min.css', __FILE__));
            // wp_enqueue_style('ts-ip-blocker-app-skeleton-css', plugins_url('../css/skeleton.css', __FILE__));            
            // wp_enqueue_style('ts-ip-blocker-app-normalize-css', plugins_url('../css/normalize.css', __FILE__));
            // wp_enqueue_style('ts-ip-blocker-app-global-css', plugins_url('../css/global.css', __FILE__));
                        
            if (strpos($hook, "ts-ip-blocker-overview") !== false){
                wp_enqueue_style('ts-ip-blocker-app-jquery-ui-css', plugins_url('../css/jquery-ui.css',__FILE__));
                wp_enqueue_style('ts-ip-blocker-app-jquery-ui-structure-css', plugins_url('../css/jquery-ui.structure.css',__FILE__));
                wp_enqueue_style('ts-ip-blocker-app-jquery-ui-theme-css', plugins_url('../css/jquery-ui.theme.css',__FILE__));                
                wp_enqueue_script('ts-ip-blocker-app-chart-js', plugins_url('../js/Chart.min.js', __FILE__), array('jquery'));
                wp_enqueue_script('ts-ip-blocker-app-overview-js', plugins_url('../js/overview.js', __FILE__), array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker'));
            }else if (strpos($hook, "ts-ip-blocker-rules") !== false){
                wp_enqueue_script('ts-ip-blocker-app-rules-js', plugins_url('../js/rules.js', __FILE__), array('jquery'));
            }else if (strpos($hook, "ts-ip-blocker-settings") !== false){
                wp_enqueue_script('ts-ip-blocker-app-settings-js', plugins_url('../js/settings.js', __FILE__), array('jquery'));
            }else if (strpos($hook, "ts-ip-blocker-traffic") !== false){
                wp_enqueue_style('ts-ip-blocker-app-jquery-ui-css', plugins_url('../css/jquery-ui.css',__FILE__));
                wp_enqueue_style('ts-ip-blocker-app-jquery-ui-structure-css', plugins_url('../css/jquery-ui.structure.css',__FILE__));
                wp_enqueue_style('ts-ip-blocker-app-jquery-ui-theme-css', plugins_url('../css/jquery-ui.theme.css',__FILE__));
                wp_enqueue_script('ts-ip-blocker-app-traffic-js', plugins_url('../js/traffic.js',__FILE__), array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker'));
            }

            $ts_ip_blocker_ajax_object = array('ajax_url' => admin_url('admin-ajax.php'), 'data' => []);
            wp_localize_script('ts-ip-blocker-app-script', 'ts_ip_blocker_ajax_object', $ts_ip_blocker_ajax_object);
        }        
    }

    public static function deactivate()
    {
        ScheduledJob::deactivate();
    }

    public static function uninstall()
    {                
        Schema::dropTables();
        Settings.delete();        
    }

    public static function adminMenus()
    {
        add_menu_page('IP Blocker', 'IP Blocker', 'activate_plugins', 'ts-ip-blocker-overview', 'TSIPBlocker\App::menuOverview', plugins_url('../images/ip-blocker-icon.png',__FILE__));
        add_submenu_page("ts-ip-blocker-overview", "Overview", "Overview", "activate_plugins", "ts-ip-blocker-overview", 'TSIPBlocker\App::menuOverview');
        add_submenu_page("ts-ip-blocker-overview", "Traffic", "Traffic", "activate_plugins", "ts-ip-blocker-traffic", 'TSIPBlocker\App::menuTraffic');
        add_submenu_page("ts-ip-blocker-overview", "Rules", "Rules", "activate_plugins", "ts-ip-blocker-rules", 'TSIPBlocker\App::menuRules');
        add_submenu_page("ts-ip-blocker-overview", "Settings", "Settings", "activate_plugins", "ts-ip-blocker-settings", 'TSIPBlocker\App::menuSettings');
    }

    public static function menuOverview()
    {
        require  plugin_dir_path(__FILE__) . '../views/overview.php';
    }

    public static function menuRules()
    {
        require  plugin_dir_path(__FILE__) . '../views/rules.php';
    }

    public static function menuTraffic()
    {
        require  plugin_dir_path(__FILE__) . '../views/traffic.php';
    }

    public static function menuSettings()
    {
        require  plugin_dir_path(__FILE__) . '../views/settings.php';
    }
}

?>