<?php
namespace TSIPBlocker;

if ( ! defined( 'ABSPATH' ) ) exit;

class Settings {

    // select * from wp_options where option_name like 'ts_ip%';

    const ACTIVE = 'ts_ip_blocker_active';
    const AUTO_PURGE_HITS_DATA = 'ts_ip_blocker_auto_purge_hits_data';
    const BLOCKING_MODE = 'ts_ip_blocker_blocking_mode';
    const MAX_HITS_ROW = 'ts_ip_blocker_max_hits_row';

    public static function get($key){
        return get_option($key);
    }

    public static function set($key, $val){
        return update_option($key, $val);
    }

    public static function reset() {
        update_option(Settings::ACTIVE, 1);
        update_option(Settings::AUTO_PURGE_HITS_DATA, 1);
        update_option(Settings::BLOCKING_MODE, 0);
        update_option(Settings::MAX_HITS_ROW, 10000);
    }

    public static function deleteAll() {
        delete_option(Settings::ACTIVE);
        delete_option(Settings::AUTO_PURGE_HITS_DATA);
        delete_option(Settings::BLOCKING_MODE);
        delete_option(Settings::MAX_HITS_ROW);
    }

}