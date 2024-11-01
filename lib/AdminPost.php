<?php
namespace TSIPBlocker;
if ( ! defined( 'ABSPATH' ) ) exit;

require_once('Util.php');
require_once('Hit.php');
require_once('Rule.php');

class AdminPost {

    public static function createRule() {
        $rule = new Rule();
        $rule->ip_address_query = sanitize_text_field($_POST['ip_address_query']);

        if(isset($_POST['hostname_query'])){
            $rule->hostname_query = sanitize_text_field($_POST['hostname_query']);
        }
        if(isset($_POST['referer_query'])){
            $rule->referer_query = sanitize_text_field($_POST['referer_query']);
        }
        if(isset($_POST['ua_query'])){
            $rule->ua_query = sanitize_text_field($_POST['ua_query']);
        }
        
        $rule->rule_name = sanitize_text_field($_POST['rule_name']);

        if($rule->save()){
            wp_redirect( admin_url("admin.php?page=ts-ip-blocker-rules&tsipb_success=" . urlencode("Rule successfully created")));
            exit;
        }else{
            wp_redirect( admin_url("admin.php?page=ts-ip-blocker-rules&tsipb_error=" . urlencode("Error occurred: ". $rule->errors[0])));
            exit;
        }
    }    

    public static function deleteRule() {
        $rule_id = intval($_POST['rule_id']);
        Rule::delete($rule_id);
        wp_redirect( admin_url("admin.php?page=ts-ip-blocker-rules&tsipb_success=" . urlencode("Rule successfully deleted")));
        exit;
    }


    //Traffic Page
    public static function blockIp() {
        $rule = new Rule();
        $ip_address = $_POST['ip_address'];        
        $rule->ip_address_query = sanitize_text_field($ip_address);
        $rule->rule_name = "Block $ip_address";
        $success_msg = "$ip_address successfully blocked";
        // $success_msg = str_replace('.', '%2E', $success_msg);

        if($rule->save()){
            wp_redirect( admin_url("admin.php?page=ts-ip-blocker-traffic&tsipb_success=" . urlencode($success_msg)));
            exit;
        }else{
            wp_redirect( admin_url("admin.php?page=ts-ip-blocker-traffic&tsipb_error=" . urlencode("Error occurred: ". $rule->errors[0])));
            exit;
        }
    }

    //Settings
    public static function updateGeneralSettings() {
        $auto_purge = intval(sanitize_text_field($_POST['auto_purge_hits_data']));
        $blocking_mode = intval(sanitize_text_field($_POST['blocking_mode']));
                
        Settings::set(Settings::AUTO_PURGE_HITS_DATA, $auto_purge == 1 ? 1: 0);
        Settings::set(Settings::BLOCKING_MODE, $blocking_mode);

        wp_redirect( admin_url("admin.php?page=ts-ip-blocker-settings&tsipb_success=" . urlencode("Settings successfully updated")));
        exit;
    }

    public static function updateThreatDetectionSettings() {        
        wp_redirect( admin_url("admin.php?page=ts-ip-blocker-settings&tsipb_success=" . urlencode("Settings successfully updated")));
        exit;
    }

    public static function updateGeolocationSettings() {
        wp_redirect( admin_url("admin.php?page=ts-ip-blocker-settings&tsipb_success=" . urlencode("Settings successfully updated")));
        exit;
    }

    public static function deleteAllHits() {        
        Hit::deleteAll();
        wp_redirect( admin_url("admin.php?page=ts-ip-blocker-settings&tsipb_success=" . urlencode("Traffic data successfully deleted")));
        exit;
    }

    public static function deleteAllRules() {
        Rule::deleteAll();
        wp_redirect( admin_url("admin.php?page=ts-ip-blocker-settings&tsipb_success=" . urlencode("All rules successfully deleted")));
        exit;
    }
    
    public static function resetApp() {
        Schema::dropTables();
        Schema::createTables();
        wp_redirect( admin_url("admin.php?page=ts-ip-blocker-settings&tsipb_success=" . urlencode("App successfully reset")));
        exit;
    }
}