<?php
namespace TSIPBlocker;
if ( ! defined( 'ABSPATH' ) ) exit;

class DateUtil {

    const DATE_FMT = "Y-m-d";
    
    public static function getPostDateWithKey($key){
        if(empty($_POST[$key])){
            return 0;
        }
        return \DateTime::createFromFormat(DateUtil::DATE_FMT, sanitize_text_field($_POST[$key]));
    }

    public static function getPostStartDate() {    
        return DateUtil::getPostDateWithKey("start_date");
    }
    public static function getPostEndDate() {    
        return DateUtil::getPostDateWithKey("end_date");
    }

    public static function firstDayOfThisMonth() {
        return \DateTime::createFromFormat(DateUtil::DATE_FMT, date("Y-m-01"));
    }
    public static function lastDayOfThisMonth() {
        return \DateTime::createFromFormat(DateUtil::DATE_FMT, date("Y-m-t"));
    }

    public static function firstDayOfThisMonthStr() {
        return \DateTime::createFromFormat(DateUtil::DATE_FMT, date("Y-m-01"))->format(DateUtil::DATE_FMT);
    }
    public static function lastDayOfThisMonthStr() {
        return \DateTime::createFromFormat(DateUtil::DATE_FMT, date("Y-m-t"))->format(DateUtil::DATE_FMT);
    }
    
}

