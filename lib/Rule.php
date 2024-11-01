<?php
namespace TSIPBlocker;

if ( ! defined( 'ABSPATH' ) ) exit;

class Rule
{
    public $id;
    public $ip_address_query;
    public $hostname_query;
    public $referer_query;
    public $ua_query;
    public $rule_name;
    public $block_count;
    public $created_at;
    public $errors = [];

    function __contstruct() {}

    public static function create($ip_address_query_p, $hostname_query_p, $referer_query_p, $ua_query_p, $rule_name_p) {
        $r = new Rule();
        $r->ip_address_query = $ip_address_query_p;
        $r->hostname_query = $hostname_query_p;
        $r->referer_query = $referer_query_p;
        $r->ua_query = $ua_query_p;
        $r->rule_name = $rule_name_p;
    }

    //Returns rule that blocks, otherwise false
    public static function shouldBlock($hit){
        foreach(Rule::getAll() as $rule){
            if($rule->blocks($hit)){
                return $rule;
            }
        }
        return false;
    }

    public function blocks($hit) {
        return $this->matchesIp($hit)
        && $this->matchesUserAgent($hit)
        && $this->matchesReferer($hit)
        && $this->matchesHostname($hit);
    }

    public function matchesIp($hit){
        if(empty($this->ip_address_query)){
            return true;
        }

        $q = trim($this->ip_address_query);
        $arr = explode("-", $q);
        $ip1 = "";

        $ip0 = trim($arr[0]);
        if(count($arr) > 1){
            $ip1 = trim($arr[1]);
        }
        $current_ip_long = ip2long($hit->ip_address);
        if(!empty($ip0)  && !empty($ip1)){
            $ip0_long = ip2long($ip0);
            $ip1_long = ip2long($ip1);
            return $current_ip_long <= $ip1_long && $current_ip_long >= $ip0_long;
        }else if (!empty($ip0)){
            $ip0_long = ip2long($ip0);
            return $current_ip_long == $ip0_long;
        }

        return false;
    }


    public function matchesHostname($hit){
        if(empty($this->hostname_query)){
            return true;
        }

        $hit->fetchHostname();
        if(empty($hit->hostname)){
            return false;
        }

        $queries = explode(",", $this->hostname_query);
        foreach($queries as $q){
            if(fnmatch($q, $hit->hostname)){
                return true;
            }
        }

        return false;
    }

    public function matchesUserAgent($hit){
        if(empty($this->ua_query)){
            return true;
        }

        if(empty($hit->ua)){
            return false;
        }

        $queries = explode(",", $this->ua_query);
        foreach($queries as $q){
            if(fnmatch($q, $hit->ua)){
                return true;
            }
        }

        return false;
    }

    public function matchesReferer($hit){
        if(empty($this->referer_query)){
            return true;
        }

        if(empty($hit->referer)){
            return false;
        }

        $queries = explode(",", $this->referer_query);
        foreach($queries as $q){
            if(fnmatch($q, $hit->referer)){
                return true;
            }
        }

        return false;
    }

    public function incrementBlockCount(){
        global $wpdb;
        $wpdb->update(Rule::tableName(), array('block_count' => ($this->block_count + 1)), array('id' => $this->id));
    }

    public function save() {
        if(!$this->valid() || !empty($this->id)) //Only save once. This should really update.
            return false;

        global $wpdb;
        $wpdb->insert(Rule::tableName(), array(
            "ip_address_query" => $this->ip_address_query,
            "hostname_query" => $this->hostname_query,
            "referer_query" => $this->referer_query,
            "ua_query" => $this->ua_query,
            "rule_name" => $this->rule_name,
            "block_count" => 0
        ));

        $this->id = $wpdb->insert_id;

        return $this;
    }

    public static function delete($rule_id) {
        if(empty($rule_id))
            return false;

        global $wpdb;
        return $wpdb->delete( Rule::tableName(), array( 'id' => $rule_id), array('%d'));
    }

    public static function getLatest($limit, $offset){
        global $wpdb;
        $q = "SELECT * FROM ".Rule::tableName()." ORDER BY created_at DESC";

        if(!empty($limit) && !empty($offset) && $limit > 0)
            $q = $q . " LIMIT $limit OFFSET $offset ";

        $results = $wpdb->get_results($q);
        $objs = [];

        foreach ($results as $row) {
            array_push($objs, Rule::fromRow($row));
        }

        return $objs;
    }

    public static function shouldBlockRequest($ip_address, $user_agent, $referer){
        return false;
    }

    public static function find($rule_id){
        if(empty($rule_id))
            return null;

        global $wpdb;
        $query = $wpdb->prepare("SELECT * FROM Rule::tableName() WHERE id = %d", $rule_id);
        $row = $wpdb->get_row($query);

        return Rule::fromRow($row);
    }

    public static function tableName() {
        global $wpdb;
        return $wpdb->prefix . 'ipb_rules';
    }

    //Validations
    public function valid(){
        $this->errors = []; //Reset errors

        if( empty($this->ip_address_query) &&
            empty($this->hostname_query) &&
            empty($this->referer_query) &&
            empty($this->ua_query)){
            array_push($this->errors, "IP address, hostname, referer or user-agent is required.");
        }

        $this->validateIpAddressQuery();
        $this->validateHostnameQuery();
        $this->validateRefererQuery();
        $this->validateUserAgentQuery();

        return sizeof($this->errors) == 0;
    }

    /*
     * Format and validate ip address query
     * Valid values:
     * `192.168.0.1`
     * `192.168.0.1 - 192.168.200.200`
    */
    public function validateIpAddressQuery(){
        if(!empty($this->ip_address_query)){
            $q = trim($this->ip_address_query);
            $dash_pos = strpos($q, "-");

            if($dash_pos === false){
                //Dash is not found. Not a range
                $this->ip_address_query = $q;
                if((!Util::isIPv6($q) && !Util::isIPv4($q)))
                    array_push($this->errors, "Invalid IP address.");

            }else{
                //Dash is found found. Query is a range
                $arr = explode("-", $q);
                $ip0 = trim($arr[0]);
                $ip1 = trim($arr[1]);

                if((!Util::isIPv6($ip0) && !Util::isIPv4($ip0)) || (!Util::isIPv6($ip1) && !Util::isIPv4($ip1)))
                    array_push($this->errors, "Invalid IP address.");

                $this->ip_address_query = "$ip0-$ip1"; //Reformat. Get rid of spaces
            }
        }
    }

    /*
     * Format and validate hostname query
    */
    public function validateHostnameQuery(){
        if(!empty($this->hostname_query)){
            $q = trim($this->hostname_query);
            $tokens = explode(",", $q);
            $new_tokens  = implode(",", array_map(function($v) {return trim($v);}, $tokens));
            $this->hostname_query = $new_tokens;
        }
    }

    /*
     * Format and validate Referer query
    */
    public function validateRefererQuery(){
        if(!empty($this->referer_query)){
            $q = trim($this->referer_query);
            $tokens = explode(",", $q);
            $new_tokens  = implode(",", array_map(function($v) {return trim($v);}, $tokens));
            $this->referer_query = $new_tokens;
        }
    }

    /*
     * Format and validate User Agent query
    */
    public function validateUserAgentQuery(){
        if(!empty($this->ua_query)){
            $q = trim($this->ua_query);
            $tokens = explode(",", $q);
            $new_tokens  = implode(",", array_map(function($v) {return trim($v);}, $tokens));
            $this->ua_query = $new_tokens;
        }
    }

    public static function getRulesBlockCountData($limit) {
        global $wpdb;        
        
        $q = "SELECT block_count, rule_name FROM ".Rule::tableName();
        $q .= " ORDER BY block_count ASC";
        $q .= " LIMIT ". $limit;

        $results = $wpdb->get_results($q);
        return $results;
    }

    public static function findById($rule_id){
        global $wpdb;
        $q = $wpdb->prepare("SELECT * FROM ".Rule::tableName()." WHERE id = %d", $rule_id);
        $row = $wpdb->get_row($q);
        return Rule::fromRow($row);
    }

    public static function getAll(){
        global $wpdb;
        $q = "SELECT * FROM ".Rule::tableName()." ORDER BY created_at DESC";

        $results = $wpdb->get_results($q);
        $objs = [];

        foreach ($results as $row) {
            array_push($objs, Rule::fromRow($row));
        }

        return $objs;
    }

    public static function deleteAll() {
        global $wpdb;
        $q = "DELETE FROM ".Rule::tableName()."";
        return $wpdb->query($q);
    }

    public static function fromRow($row){
        if(empty($row))
            return null;

        $rule = new Rule();
        $rule->id = $row->id;
        $rule->ip_address_query = $row->ip_address_query;
        $rule->hostname_query = $row->hostname_query;
        $rule->referer_query = $row->referer_query;
        $rule->ua_query = $row->ua_query;
        $rule->rule_name = $row->rule_name;
        $rule->block_count = $row->block_count;
        $rule->created_at = strtotime($row->created_at);

        return $rule;
    }

    public static function toJson($rule){
        if(empty($rule))
            return null;

        $json = [];
        $json['id'] = $rule->id;
        $json['ip_address_query'] = $rule->ip_address_query;
        $json['block_count'] = empty($rule->block_count) ? 0 : $rule->block_count;
        $json['hostname_query'] = $rule->hostname_query;
        $json['referer_query'] = $rule->referer_query;
        $json['ua_query'] = $rule->ua_query;
        $json['rule_name'] = empty($rule->rule_name) ? "-" : $rule->rule_name;

        return $json;
    }
}
