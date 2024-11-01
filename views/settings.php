<?php
if ( ! defined( 'ABSPATH' ) ) exit;
require_once(plugin_dir_path(__FILE__) . '../lib/Util.php');
require_once(plugin_dir_path(__FILE__) . '../lib/Settings.php');

add_thickbox();

$blocking_mode = intval(TSIPBlocker\Settings::get(TSIPBlocker\Settings::BLOCKING_MODE));
$auto_purge = intval(TSIPBlocker\Settings::get(TSIPBlocker\Settings::AUTO_PURGE_HITS_DATA));
?>

<?php include_once("mp_register.php"); ?>

<style>
    #filter-section .input-text-wrap{
        margin-bottom: 10px;
    }
    .form-table th {
        width: 250px;
    }
</style>
<div class="wrap">
    <h1>IP Blocker Settings</h1>

    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-1">
            <div class="postbox-container">                
                <div class="postbox">                    
                    <div class="meta-box-sortables">
                        <h2 class="hndle"><span>General</span></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tbody>
                                    <form action="<?php echo TSIPBlocker\Util::getAdminPostUrl(); ?>" method="post" id="#settings">
                                        <input type="hidden" name="action" value="ts_ip_blocker_update_general_settings">                                    
                                        <tr>
                                            <th scope="row">Periodically Purge Traffic Data</th>
                                            <td>
                                                <input type="checkbox" name="auto_purge_hits_data" <?php echo ($auto_purge == 1 ? "checked" : ""); ?> value="1">
                                            </td>
                                        </tr>                                    

                                        <tr>
                                            <th scope="row">Blocking Mode</th>
                                            <td>
                                                <select name="blocking_mode">
                                                    <option value="0" <?php echo ($blocking_mode == 0 ? "selected" : ""); ?>>Page Not Found</option>
                                                    <option value="1" <?php echo ($blocking_mode == 1 ? "selected" : ""); ?>>Blank Page</option>
                                                </select>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th scope="row"><input type="submit" class="button button-primary" value="Save"></th>
                                            <td></td>
                                        </tr>
                                    </form>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>    
            </div>

            <p><a href="#" id="adv-settings-toggle">Show Advanced Settings</a></p>
    
            <div class="postbox-container" id="adv-settings-section" style="display:none;">                
                <div class="postbox">                    
                    <div class="meta-box-sortables">
                        <h2 class="hndle"><span>Advanced Settings</span></h2>
                        <div class="inside">
                            <p>
                                These actions are permanent and cannot be undone.
                            </p>

                            <form action="<?php echo TSIPBlocker\Util::getAdminPostUrl(); ?>" method="post" style="display: inline;">
                                <input type="hidden" name="action" value="ts_ip_blocker_delete_all_hits">
                                <input type="submit" class="button button-primary" value="Delete Traffic Data" >
                            </form>

                            <form action="<?php echo TSIPBlocker\Util::getAdminPostUrl(); ?>" method="post" style="display: inline;">
                                <input type="hidden" name="action" value="ts_ip_blocker_delete_all_rules">
                                <input type="submit" class="button button-primary" value="Delete All Rules">                                        
                            </form>                                            

                            <form action="<?php echo TSIPBlocker\Util::getAdminPostUrl(); ?>" method="post" style="display: inline;">
                                <input type="hidden" name="action" value="ts_ip_blocker_reset_app">
                                <input type="submit" class="button button-primary" value="Reset Everything">                                        
                            </form>
                        </div>
                    </div>
                </div>    
            </div>
        </div>
    </div>
</div>

<script>
jQuery(function(){
    mixpanel.track("settings-page");
    mixpanel.track_forms("#settings", "saved-settings");
    SettingsPage.init();
});
</script>