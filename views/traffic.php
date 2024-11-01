<?php
if ( ! defined( 'ABSPATH' ) ) exit;

require_once(plugin_dir_path(__FILE__) . '../lib/DateUtil.php');

add_thickbox();

$start_date = \TSIPBlocker\DateUtil::firstDayOfThisMonthStr();
$end_date = \TSIPBlocker\DateUtil::lastDayOfThisMonthStr();
?>

<?php include_once("mp_register.php"); ?>

<style>
    #filter-section .input-text-wrap{
        margin-bottom: 10px;
    }
</style>
<div class="wrap">
    <h1>Traffic</h1>

    <div class="media-toolbar wp-filter">
        <div class="media-toolbar-secondary">
            <div class="view-switch media-grid-view-switch">
                
                <select  id="blocked-status" style="margin-left: 10px;">
                    <option value="0">All Blocked Status</option>
                    <option value="1">Blocked</option>
                    <option value="2">Not Blocked</option>
                </select>
                
                <select  id="page-status" style="margin-left: 10px;">
                    <option value="0">All Page Status</option>
                    <option value="1">Success</option>
                    <option value="2">Page Not Found</option>
                </select>
                
                <input type="text" autocomplete="off" value="" placeholder="Start Date" id="start-date"  style="margin-left: 10px; vertical-align: middle;">                                                
                <input type="text" autocomplete="off" value="" placeholder="End Date" id="end-date" style="margin-left: 10px; vertical-align: middle;">

                <button id="filter" class="button button-primary" style="margin-left: 10px;">Apply</button>
            </div>

        </div>    
    </div>
    
    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-1">
            <div id="postbox-container-1" class="postbox-container">                
                <div class="postbox">
                    <table class="wp-list-table widefat fixed striped users">
                        <thead>
                            <tr>
                                <td><strong>Time</strong></td>
                                <td><strong>URL</strong></td>
                                <td><strong>IP Adress</strong></td>
                                <td><strong>Refererer</strong></td>
                                <td><strong>Browser</strong></td>
                                <td><strong>Blocked</strong></td>
                                <td><strong>Status</strong></td>
                                <td><strong>Actions</strong></td>
                            </tr>
                        </thead>
                        <tbody id="hits-list">
                            <tr id="hit-detail-tpl" style="display:none;" class="hit-detail">
                                <td class="time">-</td>
                                <td class="url">-</td>
                                <td class="ip-address">-</td>
                                <td class="referer">-</td>
                                <td class="browser">-</td>
                                <td class="blocked">-</td>
                                <td class="status">-</td>
                                <td class="actions">

                                    <a href="" class="button button-small thickbox view-detail-link" style="display:inline-block;">
                                        <i class="fa fa-eye view-detail" aria-hidden="true" style="cursor: pointer;"></i>
                                        <div style="display:none;" class="detail-container">
                                            <strong>URL</strong><br>
                                            <span class="d-url">-</span>
                                            <br><br>

                                            <strong>IP Adress</strong><br>
                                            <span class="d-ip-address">-</span>
                                            <br><br>

                                            <strong>Hostname </strong><br>
                                             <span class="d-hostname">-</span>
                                             <br><br>                         

                                            <strong>User-Agent</strong><br>
                                             <span class="d-ua">-</span>
                                             <br><br>

                                            <strong>Referer</strong><br> 
                                            <span class="d-referer">-</span>
                                            <br><br>

                                            <strong>Time</strong><br> 
                                            <span class="d-time">-</span>
                                            <br><br>

                                            <strong>Threat Type</strong><br>
                                            <span class="d-gsb-threat-type">-</span>
                                            <br><br>                  

                                            <strong>Blocked</strong><br>
                                            <span class="d-blocked">-</span>
                                            <br><br>

                                            <strong>Page Not Found</strong><br>
                                            <span class="d-page-not-found">-</span>
                                            <br><br>                                                                                        
                                        </div>
                                    </a>	
                                    
                                    <!--<i class="fa fa-ban block-ip" aria-hidden="true" style="cursor: pointer; color: red;"></i>-->
                                    <form action="<?php echo TSIPBlocker\Util::getAdminPostUrl(); ?>" method="post" style="display:inline;" class="block-ip">
                                        <input type="hidden" name="action" value="ts_ip_blocker_block_ip">
                                        <input type="hidden" name="ip_address" value="">
                                        <input type="submit" class="button button-small delete" value="Block IP">
                                        <!--<button class="button button-small delete">
                                            <i class="fa fa-ban" aria-hidden="true" style="cursor: pointer;"></i>
                                        </button>-->
                                    </form>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(function(){
    mixpanel.track("traffic-page");
    jQuery("#filter").click(function(){
        mixpanel.track("traffic-apply-filter");
    });
    TrafficPage.init({
        start_date: "<?php echo $start_date; ?>",
        end_date: "<?php echo $end_date; ?>"
    });
});
</script>
