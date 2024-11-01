<?php
namespace TSIPBlocker;
if ( ! defined( 'ABSPATH' ) ) exit;

require_once('Util.php');
require_once('Hit.php');
require_once('Rule.php');
require_once('DateUtil.php');

class AdminApi {
    //Hits
    public static function getHits() {
        $limit = empty($_POST['limit']) ? 30 : intval($_POST['limit']);

        $start_date = DateUtil::getPostStartDate();
        $end_date = DateUtil::getPostEndDate();

        $page_status = empty($_POST['page_status']) ? 0 : intval($_POST['page_status']);
        $blocked_status = empty($_POST['blocked_status']) ? 0 : intval($_POST['blocked_status']);

        $hits = Hit::getHits($start_date, $end_date, $blocked_status, $page_status, $limit, $page);
        $hits['result'] = array_map("TSIPBLocker\Hit::toJson", $hits['result']);

        wp_send_json($hits);
    }


    //Overview Charts
    public static function getRulesBlockCounts() {
        $data = Rule::getRulesBlockCountData(5);
        wp_send_json(["data" => $data]);
    }

    public static function getHitsCountPerIp() {
        $start_date = DateUtil::getPostStartDate();
        $end_date = DateUtil::getPostEndDate();

        if($start_date > $end_date){
            wp_send_json(["data" => []]);
            return;
        }

        $data = Hit::getHitsCountPerIpData($start_date, $end_date);
        wp_send_json(["data" => $data]);
    }

    public static function getHitsCountPerGroup() {
        $start_date = DateUtil::getPostStartDate();
        $end_date = DateUtil::getPostEndDate();

        if($start_date > $end_date){
            wp_send_json(["data" => []]);
            return;
        }

        $data = Hit::getHitsCountPerGroupData($start_date, $end_date);
        wp_send_json(["data" => $data]);
    }

    public static function getHitsCountPerReferer() {
        $start_date = DateUtil::getPostStartDate();
        $end_date = DateUtil::getPostEndDate();

        if($start_date > $end_date){
            wp_send_json(["data" => []]);
            return;
        }

        $data = Hit::getHitsCountPerRefererData($start_date, $end_date);
        wp_send_json(["data" => $data]);
    }

    public static function getDailyHitsCount() {
        $start_date = DateUtil::getPostStartDate();
        $end_date = DateUtil::getPostEndDate();

        //Invalid date range
        if($start_date > $end_date){
            wp_send_json(["data" => []]);
            return;
        }

        $data = Hit::getDailyHitsCountData($start_date, $end_date); //[ {hits_count: 2, block_count: 3, created_at: 1-1-2017 }.... ]
            
        //Transformed Data to: { created_at: {hits_count: 2, block_count: 3} }
        $t = array(); 
        foreach($data as $d){
            $t[$d->created_at] = array("block_count" => intval($d->block_count), "hits_count" => intval($d->hits_count));
        }        

        // Fill missing data
        $cursor = $start_date;
        $end_cursor = $end_date;
        while($cursor->getTimestamp() <= $end_date->getTimestamp()) {
            $date_str = $cursor->format("Y-m-d");
            if(!array_key_exists($date_str, $t) || !isset($t[$date_str])){
                $t[$date_str] = array("block_count" => 0, "hits_count" => 0);
            }
            $cursor->add(date_interval_create_from_date_string('1 day'));
        }

        //Transform data back to array
        $arr = [];
        foreach($t as $k=>$v){            
            $arr[] = array(
                "created_at" => $k,
                "block_count" => $v["block_count"],
                "hits_count" => $v["hits_count"]
            );
        }

        usort($arr, function($a, $b){
            if ($a["created_at"] == $b["created_at"]) {return 0;}
            return (\DateTime::createFromFormat("Y-m-d", $a["created_at"]) < \DateTime::createFromFormat("Y-m-d", $b["created_at"])) ? -1 : 1;
        });

        wp_send_json(["data" => $arr]);
    }

    //------ End Overview Charts

    //Rules
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
            wp_send_json(["rule" => $rule]);
        }else{
            wp_send_json(["errors" => $rule->errors]);
        }
    }

    public static function deleteRule() {
        $rule_id = intval($_POST['rule_id']);
        Rule::delete($rule_id);
    }

    public static function getRules(){
        $rules = array_map("TSIPBlocker\Rule::toJson", Rule::getAll());
        wp_send_json(["rules" => $rules]);
    }
}