<?php
namespace TSIPBlocker;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once("Hit.php");

class ScheduledJob {
    public static function hourly() {
        // error_log("ScheduledJob::hourly called");
        // update_option("ts_ip_blocker_test", rand()); 
        Hit::purge();
    }

    public static function activate() {
        if (! wp_next_scheduled ( 'ts_ip_blocker_hourly_jobs' )) {
            wp_schedule_event(time(), 'hourly', 'ts_ip_blocker_hourly_jobs');
        }        
    }

    public static function deactivate() {
        wp_clear_scheduled_hook( 'ts_ip_blocker_hourly_jobs' );
    }
}