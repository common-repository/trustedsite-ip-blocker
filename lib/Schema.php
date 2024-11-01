<?php
namespace TSIPBlocker;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once('Hit.php');

class Schema {

    //You must put each field on its own line in your SQL statement.
    //You must have two spaces between the words PRIMARY KEY and the definition of your primary key.
    //You must use the key word KEY rather than its synonym INDEX and you must include at least one KEY.
    //KEY must be followed by a SINGLE SPACE then the key name then a space then open parenthesis with the field name then a closed parenthesis.
    //You must not use any apostrophes or backticks around field names.
    //Field types must be all lowercase.
    //SQL keywords, like CREATE TABLE and UPDATE, must be uppercase.
    //You must specify the length of all fields that accept a length parameter. int(11), for example.

    public static function createTables(){
        global $wpdb;
        $charset_collate = '';

        if ( ! empty ( $wpdb->charset ) )
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";

        if ( ! empty ( $wpdb->collate ) )
            $charset_collate .= " COLLATE $wpdb->collate";

        $hits_table = Hit::tableName();
        $hits_table_sql =
        "CREATE TABLE IF NOT EXISTS {$hits_table} (
        id int unsigned NOT NULL auto_increment ,
        url varchar(512),
        ip_address varchar(512) NOT NULL,
        hostname varchar(512),
        referer varchar(512),
        http_not_found boolean,
        country varchar(255),
        city varchar(255),
        ua varchar(512),
        blocked boolean,
        gsb_threat_type int unsigned NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
        )  $charset_collate;";

        $rules_table = Rule::tableName();
        $rules_table_sql =
        "CREATE TABLE IF NOT EXISTS {$rules_table} (
        id int unsigned NOT NULL auto_increment ,
        ip_address_query varchar(512),
        hostname_query varchar(512),
        referer_query varchar(512),
        ua_query varchar(512),
        rule_name varchar(512),
        block_count int unsigned NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
        ) $charset_collate;";

        if ( ! function_exists('dbDelta') ) {
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        }

        dbDelta( $hits_table_sql );
        dbDelta( $rules_table_sql );
    }

    public static function dropTables() {
        global $wpdb;

        $hits_table = Hit::tableName();
        $rules_table = Rule::tableName();

        $wpdb->query("DROP TABLE $hits_table");
        $wpdb->query("DROP TABLE $rules_table");
    }
}