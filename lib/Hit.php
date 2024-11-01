<?php
namespace TSIPBlocker;

if ( ! defined( 'ABSPATH' ) ) exit;

require_once("Util.php");

class Hit
{
    public $id;
    public $ip_address;
    public $blocked;
    public $url;
    public $referer;
    public $ua;
    public $http_not_found;
    public $created_at;
    public $country;
    public $city;
    public $gsb_threat_type;

    //Transient
    public $hostname;    
    public $hasBlockingRule;

    function __contstruct() {}

    public function save() {
        if(!$this->valid())
            return false;

        if(!empty($this->id)) {
            return false; //Only save once. This should really update.
        }

        global $wpdb;

        return  $wpdb->insert(Hit::tableName(), array(
            "ip_address" => $this->ip_address,
            "blocked" => $this->blocked,
            "referer" => $this->referer,
            "country" => $this->country,
            "city" => $this->city,
            "url" => $this->url,
            "ua" => $this->ua,
            "http_not_found" => $this->http_not_found
        ));
    }

    public function geoipRecord() {
        if(empty($this->ip_address))
            return null;

        $plugin_dir = plugin_dir_path(__FILE__);
        geoip_load_shared_mem($plugin_dir . "GeoLiteCity.dat");
        $gi = geoip_open($plugin_dir . "GeoLiteCity.dat", GEOIP_STANDARD);
        $record = geoip_record_by_addr($gi, $this->ip_address);
        geoip_close($gi);
        return $record;
    }

    public function fetchGeolocationData(){
       $record = $this->geoipRecord();
       if(!empty($record)){
            $this->country = $record->country_name;
            $this->city = $record->city;   
       }       
    }
    
    public function fetchHostname(){
        global $wpdb;
        $this->hostname = gethostbyaddr($this->ip_address);
        $wpdb->update(Hit::tableName(), array('hostname' => $this->hostname, array('id' => $this->id)));
        return $this->hostname;
    }

    public function valid() {
        return true;
    }

    public static function tableName(){
        global $wpdb;
        return $wpdb->prefix . 'ipb_hits';
    }

    public static function log() {
        $can_log = Hit::canLog();
        $ip_address = Hit::getIPAddress();

        if(!$can_log || empty($ip_address))
            return;

        $not_found = is_404();
        
        error_log("Not found: " . $not_found);

        $hit = new Hit();
        $hit->ip_address = $ip_address;
        $hit->blocked = false;
        $hit->url = Hit::getRequestURL();
        $hit->referer = Hit::getReferer();
        $hit->ua = Hit::getUserAgent();        
        $hit->http_not_found = $not_found;
        // $hit->fetchGeolocationData();
        $blocker = Rule::shouldBlock($hit);

        //If request is accepted and there is a matching blocker... the block this request
        if($blocker !== false ){
            $hit->blocked = true;
            $blocker->incrementBlockCount();
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
        }

        $hit->save();
    }

    public static function getHitsCountPerGroupData($start_date, $end_date){
        global $wpdb;

        $q = "SELECT COUNT(id) hits_count, SUM(blocked) block_count FROM ".Hit::tableName();
        $q .= " WHERE created_at >= '" . $start_date->format("Y-m-d") ."'";
        $q .= " AND created_at <= '" . $end_date->format("Y-m-d") ."'";

        $row = $wpdb->get_row($q);

        $hits_count = intval($row->hits_count);
        $block_count = intval($row->block_count);

        return array(
            "accepted_count" => ($hits_count - $block_count),
            "block_count" => $block_count
        );
    }

    public static function getHitsCountPerIpData($start_date, $end_date){
        global $wpdb;

        $q = "SELECT ip_address, COUNT(id) hits_count FROM ".Hit::tableName();
        $q .= " WHERE created_at >= '" . $start_date->format("Y-m-d") ."'";
        $q .= " AND created_at <= '" . $end_date->format("Y-m-d") ."'";
        $q .= " GROUP BY ip_address";
        $q .= " ORDER BY hits_count DESC";
        $q .= " LIMIT 5";

        error_log($q);

        $row = $wpdb->get_results($q);

        return $row;
    }

    public static function getHitsCountPerRefererData($start_date, $end_date){
        global $wpdb;

        $q = "SELECT referer, COUNT(id) hits_count FROM ".Hit::tableName();
        $q .= " WHERE created_at >= '" . $start_date->format("Y-m-d") ."'";
        $q .= " AND created_at <= '" . $end_date->format("Y-m-d") ."'";
        $q .= " GROUP BY referer";
        $q .= " ORDER BY hits_count DESC";
        $q .= " LIMIT 5";

        $row = $wpdb->get_results($q);

        return $row;
    }

    public static function getDailyHitsCountData($start_date, $end_date){
        global $wpdb;
        
        $start_date_str = $start_date->format("Y-m-d");
        $end_date_str = $end_date->format("Y-m-d");
        
        $q = "SELECT COUNT(id) hits_count, SUM(blocked) block_count, DATE(created_at) created_at FROM ".Hit::tableName();
        $q .= " WHERE created_at >= '" . $start_date_str ."'";
        $q .= " AND created_at <= '" . $end_date_str ."'";
        $q .= " GROUP BY DATE(created_at)";
        $q .= " ORDER BY created_at ASC";

        $results = $wpdb->get_results($q);
        return $results;
    }    

    public static function getMostBlockedIps() {
        global $wpdb;
        return $wpdb->get_results("SELECT ip_address, SUM(blocked) block_count FROM ".Hit::tableName()." GROUP BY ip_address HAVING block_count > 0");
    }

    public static function getHits($start_date, $end_date, $blocked_status, $page_status, $limit, $page) {
        global $wpdb;

        $q = Hit::tableName();

        $wheres = [];

        if(!empty($start_date)) {
            $start_date_str = $start_date->format("Y-m-d");
            array_push($wheres, "created_at >= '" . $start_date_str ."'");
        }
        if(!empty($end_date)) {
            $end_date_str = $end_date->format("Y-m-d");
            array_push($wheres, "created_at <= '" . $end_date_str ."'");
        }
        
        $bs = intval($blocked_status);
        if($bs > 0){
            if($bs == 1){
                array_push($wheres, "blocked = 1");
            }else if($bs == 2){
                array_push($wheres, "blocked = 0");
            }
        }

        $ps = intval($page_status);
        if($ps > 0){
            if($ps == 1){
                array_push($wheres, "http_not_found = 1");
            }else if($ps == 2){
                array_push($wheres, "http_not_found = 0");
            }
        }

        if(count($wheres) > 0) {
            $joined_wheres = implode(" AND ", $wheres);
            $q .= " WHERE " . $joined_wheres;
        }        

        $q .= " ORDER BY created_at DESC";
        if(!empty($limit) && $limit > 0)
            $q .= " LIMIT $limit";

        $results_q = "SELECT * FROM " . $q;
        if(!empty($page)) {
            $offset = $page * $limit;
            $results_q .= " OFFSET $offset";
        }

        $count_q = "SELECT COUNT(*) AS `total_count` FROM " . $q;
        $results = $wpdb->get_results($results_q);

        $count_result = $wpdb->get_results($count_q, ARRAY_A);
        $total_count = $count_result[0]['total_count'];

        $objs = [];

        $rules = Rule::getAll();

        foreach ($results as $r) {
            $h = new Hit();
            $h->id = $r->id;
            $h->ip_address = $r->ip_address;
            $h->blocked = $r->blocked;
            $h->url = $r->url;
            $h->referer = $r->referer;
            $h->ua = $r->ua;
            $h->http_not_found = $r->http_not_found;
            $h->hostname = gethostbyaddr($h->ip_address);
            $h->created_at = strtotime($r->created_at);
            $h->country = $r->country;
            $h->city = $r->city;
            $h->hasBlockingRule = false;

            foreach($rules as $rule) {
                if($rule->blocks($h)){
                    $h->hasBlockingRule = true;
                    break;
                }
            }

            array_push($objs, $h);
        }

        $total_pages = ($limit == 0 ? 0 : ($total_count / $limit)) + 1;

        return array(
            "per_page" => $limit, 
            "total_count" => $count_result[0]['total_count'], 
            "total_pages" => intval($total_pages),   
            "current_page" => $page,
            "result" => $objs
        );
    }

    public static function getLatest($timeframe, $limit) {
        global $wpdb;
        $q = "SELECT * FROM ".Hit::tableName();
        $t = intval($timeframe);

        if($t != 4){
            if($t == 0){ //This week
                $dtstr = date( "Y-m-d", strtotime("7 days ago"));
            }else if($t == 1){ //This Month
                $dtstr = date( "Y-m-d", strtotime("30 days ago"));
            }else if($t == 2){ //Last 3 Month
                $dtstr = date( "Y-m-d", strtotime("90 days ago"));
            }else{ //This year
                $curr_year = date('Y');
                $dtstr = date( $curr_year . "-1-1");
            }
            $q .= " WHERE created_at >= '" . $dtstr ."'";
        }

        $q .= " ORDER BY created_at DESC";

        if(!empty($limit) && $limit > 0)
            $q = $q . " LIMIT $limit";

        $results = $wpdb->get_results($q);
        $objs = [];

        foreach ($results as $r) {
            $h = new Hit();
            $h->id = $r->id;
            $h->ip_address = $r->ip_address;
            $h->blocked = $r->blocked;
            $h->url = $r->url;
            $h->referer = $r->referer;
            $h->ua = $r->ua;
            $h->http_not_found = $r->http_not_found;
            $h->hostname = gethostbyaddr($h->ip_address);
            $h->created_at = strtotime($r->created_at);
            array_push($objs, $h);
        }

        return $objs;
    }

    public static function canLog(){
        global $wp_version;
        
        if(is_admin()){
            return false;
        }

        if(isset($_SERVER['HTTP_USER_AGENT'])){
            if(preg_match('/WordPress\/' . $wp_version . '/i', $_SERVER['HTTP_USER_AGENT']))
                return false;
        }

        return true;
    }

    public static function deleteAll() {
        global $wpdb;
        $q = "DELETE FROM ".Hit::tableName()."";
        return $wpdb->query($q);
    }

    public static function purge() {
        global $wpdb;

        $count_q = "SELECT COUNT(*) as row_count FROM ".Hit::tableName();
        $count_r = $wpdb->get_row($count_q);

        if(intval($count_r->row_count) >= 10000) {
            $delete_q = "DELETE FROM ".Hit::tableName()." ORDER BY created_at DESC LIMIT 7000";
            $wpdb->get_row($delete_q);
        }
    }

    public static function getUserAgent(){
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    }

    public static function getReferer() {
        return (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
    }

    public static function getRequestURL(){
        if(isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST']){
            $host = $_SERVER['HTTP_HOST'];
        } else {
            $host = $_SERVER['SERVER_NAME'];
        }
        $proto = 'http';
        if( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ){
            $proto = 'https';
        }
        return $proto . '://' . $host . $_SERVER['REQUEST_URI'];
    }

    public static function getIPAddress() {
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        if(!empty($ip) && (Util::isIPv4($ip) || Util::isIPv6($ip))){
            return $ip;
        }
        return null;
    }

    public static function toJson($hit){
        if(empty($hit))
            return null;

        $json = [];
        $json['id'] = $hit->id;
        $json['url'] = $hit->url;
        $json['ip_address'] = $hit->ip_address;
        $json['has_blocking_rule'] = $hit->hasBlockingRule ? 1 : 0;
        $json['http_not_found'] = $hit->http_not_found;
        $json['referer'] = $hit->referer;
        $json['browser'] = Hit::getBrowser($hit->ua)["name"];
        $json['ua_full'] = $hit->ua;
        $json['blocked'] = $hit->blocked;
        $json['hostname'] = $hit->hostname;
        $json['country'] = $hit->country;
        $json['city'] = $hit->city;
        $json['created_at'] = gmdate("Y-m-d @ g:i a", $hit->created_at);;

        return $json;
    }

    public static function getBrowser($ua)
    {
        $u_agent = $ua;
        $bname = 'Unknown';
        $platform = 'Unknown';
        $version= "";

        //First get the platform?
        if (preg_match('/linux/i', $u_agent)) {
            $platform = 'linux';
        }
        elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
            $platform = 'mac';
        }
        elseif (preg_match('/windows|win32/i', $u_agent)) {
            $platform = 'windows';
        }

        // Next get the name of the useragent yes seperately and for good reason
        $ub = '';

        if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent))
        {
            $bname = 'Internet Explorer';
            $ub = "MSIE";
        }
        elseif(preg_match('/Firefox/i',$u_agent))
        {
            $bname = 'Mozilla Firefox';
            $ub = "Firefox";
        }
        elseif(preg_match('/Chrome/i',$u_agent))
        {
            $bname = 'Google Chrome';
            $ub = "Chrome";
        }
        elseif(preg_match('/Safari/i',$u_agent))
        {
            $bname = 'Apple Safari';
            $ub = "Safari";
        }
        elseif(preg_match('/Opera/i',$u_agent))
        {
            $bname = 'Opera';
            $ub = "Opera";
        }
        elseif(preg_match('/Netscape/i',$u_agent))
        {
            $bname = 'Netscape';
            $ub = "Netscape";
        }

        // finally get the correct version number
        $known = array('Version', $ub, 'other');
        $pattern = '#(?<browser>' . join('|', $known) .
            ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (!preg_match_all($pattern, $u_agent, $matches)) {
            // we have no matching number just continue
        }

        // see how many we have
        $i = count($matches['browser']);
        if ($i != 1) {
            //we will have two since we are not using 'other' argument yet
            //see if version is before or after the name
            if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
                $version= $matches['version'][0];
            }else {
                $version= $matches['version'][1];
            }
        }else {
            $version= $matches['version'][0];
        }

        // check if we have a number
        if ($version==null || $version=="") {$version="?";}

        return array(
            'userAgent' => $u_agent,
            'name'      => $bname,
            'version'   => $version,
            'platform'  => $platform,
            'pattern'    => $pattern
        );
    }
}