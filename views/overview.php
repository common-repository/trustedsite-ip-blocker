<?php 
if ( ! defined( 'ABSPATH' ) ) exit; 

require_once(plugin_dir_path(__FILE__) . '../lib/Hit.php');
require_once(plugin_dir_path(__FILE__) . '../lib/DateUtil.php');

$start_date = \TSIPBlocker\DateUtil::firstDayOfThisMonthStr();
$end_date = \TSIPBlocker\DateUtil::lastDayOfThisMonthStr();
$fmt = "Y-m-d";

// date("Y-m-d", strtotime("2017-01-22"));
if(array_key_exists('start_date', $_GET)&& isset($_GET['start_date'])){
    $start_date = date($fmt, strtotime($_GET['start_date']));
}
if(array_key_exists('end_date', $_GET)&& isset($_GET['end_date'])){
    $end_date = date($fmt, strtotime($_GET['end_date']));
}

$minus_month_start_date = \DateTime::createFromFormat($fmt, $start_date)->add(date_interval_create_from_date_string('-30 days'));
$minus_month_end_date = \DateTime::createFromFormat($fmt, $end_date)->add(date_interval_create_from_date_string('-30 days'));

$minus_week_start_date = \DateTime::createFromFormat($fmt, $start_date)->add(date_interval_create_from_date_string('-7 days'));
$minus_week_end_date = \DateTime::createFromFormat($fmt, $end_date)->add(date_interval_create_from_date_string('-7 days'));

$add_month_start_date = \DateTime::createFromFormat($fmt, $start_date)->add(date_interval_create_from_date_string('30 days'));
$add_month_end_date = \DateTime::createFromFormat($fmt, $end_date)->add(date_interval_create_from_date_string('30 days'));

$add_week_start_date = \DateTime::createFromFormat($fmt, $start_date)->add(date_interval_create_from_date_string('7 days'));
$add_week_end_date = \DateTime::createFromFormat($fmt, $end_date)->add(date_interval_create_from_date_string('7 days'));

$this_week_start_date = \DateTime::createFromFormat($fmt, date("Y-m-d", strtotime('monday this week')));
$this_week_end_date = \DateTime::createFromFormat($fmt, date("Y-m-d", strtotime('sunday this week')));

$this_month_start_date = \DateTime::createFromFormat($fmt, date("Y-m-01"));
$this_month_end_date = \DateTime::createFromFormat($fmt, date("Y-m-t"));
?>

<?php include_once("mp_register.php"); ?>

<div class="wrap">
    <h1>IP Blocker </h1>
        
    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-1">
            <div class="postbox-container">                
                <div class="postbox">
                    <div class="meta-box-sortables">
                        <h2 class="hndle"><span>Chart Settings</span></h2>
                        <div class="inside">
                            <form action="<?php echo TSIPBlocker\Util::getAdminUrl(); ?>admin.php" method="get" style="display: inline-block;" id="apply-filter">
                                <input type="hidden" name="page" value="ts-ip-blocker-overview">
                                <input type="text" autocomplete="off" value="" placeholder="Start Date" style="display: inline-block;" id="start-date" name="start_date">
                                <input type="text" autocomplete="off" value="" placeholder="End Date" style="display: inline-block;" id="end-date" name="end_date">
                                <input type="submit" class="button button-primary" value="Apply">
                            </form>

                            <form action="<?php echo TSIPBlocker\Util::getAdminUrl(); ?>admin.php" method="get" style="display: inline;" id="min-1month">
                                <input type="hidden" name="page" value="ts-ip-blocker-overview">
                                <input type="hidden" name="start_date" value="<?php echo $minus_month_start_date->format('Y-m-d'); ?>">
                                <input type="hidden" name="end_date" value="<?php echo $minus_month_end_date->format('Y-m-d'); ?>">
                                <input type="submit" class="button button-secondary" value="-1 Month" style="margin-left: 20px;">
                            </form>

                            <form action="<?php echo TSIPBlocker\Util::getAdminUrl(); ?>admin.php" method="get" style="display: inline;" id="min-1week">
                                <input type="hidden" name="page" value="ts-ip-blocker-overview">
                                <input type="hidden" name="start_date" value="<?php echo $minus_week_start_date->format('Y-m-d'); ?>">
                                <input type="hidden" name="end_date" value="<?php echo $minus_week_end_date->format('Y-m-d'); ?>">
                                <input type="submit" class="button button-secondary" value="-1 Week" >
                            </form>
                            
                            <form action="<?php echo TSIPBlocker\Util::getAdminUrl(); ?>admin.php" method="get" style="display: inline;" id="this-week">
                                <input type="hidden" name="page" value="ts-ip-blocker-overview">
                                <input type="hidden" name="start_date" value="<?php echo $this_week_start_date->format('Y-m-d'); ?>">
                                <input type="hidden" name="end_date" value="<?php echo $this_week_end_date->format('Y-m-d'); ?>">
                                <input type="submit" class="button button-secondary" value="This Week" >
                            </form>

                            <form action="<?php echo TSIPBlocker\Util::getAdminUrl(); ?>admin.php" method="get" style="display: inline;" id="this-month">
                                <input type="hidden" name="page" value="ts-ip-blocker-overview">
                                <input type="hidden" name="start_date" value="<?php echo $this_month_start_date->format('Y-m-d'); ?>">
                                <input type="hidden" name="end_date" value="<?php echo $this_month_end_date->format('Y-m-d'); ?>">  
                                <input type="submit" class="button button-secondary" value="This Month" >
                            </form>

                            <form action="<?php echo TSIPBlocker\Util::getAdminUrl(); ?>admin.php" method="get" style="display: inline;" id="plus-1week">
                                <input type="hidden" name="page" value="ts-ip-blocker-overview">
                                <input type="hidden" name="start_date" value="<?php echo $add_week_start_date->format('Y-m-d'); ?>">
                                <input type="hidden" name="end_date" value="<?php echo $add_week_end_date->format('Y-m-d'); ?>">
                                <input type="submit" class="button button-secondary" value="+1 Week" >
                            </form>

                            <form action="<?php echo TSIPBlocker\Util::getAdminUrl(); ?>admin.php" method="get" style="display: inline;" id="plus-1month">
                                <input type="hidden" name="page" value="ts-ip-blocker-overview">
                                <input type="hidden" name="start_date" value="<?php echo $add_month_start_date->format('Y-m-d'); ?>">
                                <input type="hidden" name="end_date" value="<?php echo $add_month_end_date->format('Y-m-d'); ?>">
                                <input type="submit" class="button button-secondary" value="+1 Month" >
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--Close #post-body -->

        <div id="post-body" class="metabox-holder columns-1">
            <div class="postbox-container">                
                <div class="postbox">
                    <div class="meta-box-sortables">
                        <h2 class="hndle"><span>Daily Traffic</span></h2>
                        <div class="inside">
                            <canvas id="traffic-chart" height="50"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--Close #post-body -->

        <div id="post-body" class="metabox-holder columns-1">

            <div id="postbox-container-2" class="postbox-container" style="width: 33%;">
                <div class="postbox">
                    <div class="meta-box-sortables">
                        <h2 class="hndle"><span>Accepted vs Blocked</span></h2>
                        <div class="inside">
                            <canvas id="accepted-vs-blocked" style="height: 250px;">
                            </canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div id="postbox-container-1" class="postbox-container" style="width: 33%; margin-left: 5px;">
                <div class="postbox">
                    <div class="meta-box-sortables">
                        <h2 class="hndle"><span>Traffic by Referer</span></h2>
                        <div class="inside">
                            <canvas id="traffic-per-referer" style="height: 250px;">
                            </canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div id="postbox-container-1" class="postbox-container" style="width: 33%; margin-left: 5px;">
                <div class="postbox">
                    <div class="meta-box-sortables">
                        <h2 class="hndle"><span>Block Counts per Rule</span></h2>
                        <div class="inside">
                            <canvas id="rules-block-counts" style="height: 250px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div> 
        <!--Close #post-body -->

    </div>
    <!--Close #poststuff -->
</div>

<script>
jQuery(function(){
    mixpanel.track("overview-page");
    mixpanel.track_forms("#apply-filter", "overview-apply-filter");
    mixpanel.track_forms("#min-1month", "overview-filter-min-month");
    mixpanel.track_forms("#min-1week", "overview-filter-min-month");
    mixpanel.track_forms("#this-week", "overview-filter-this-week");
    mixpanel.track_forms("#this-month", "overview-filter-this-month");
    mixpanel.track_forms("#plus-1month", "overview-filter-plus-month");
    mixpanel.track_forms("#plus-1week", "overview-filter-plus-week");
    OverviewPage.init({
        start_date: "<?php echo $start_date; ?>",
        end_date: "<?php echo $end_date; ?>"
    });
});
</script>