<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php include_once("mp_register.php"); ?>

<div class="wrap">
    <h1>Rules</h1>

    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <div id="postbox-container-1" class="postbox-container">
                <div class="postbox">
                    <div class="meta-box-sortables">
                        <h2 class="hndle"><span>New Rule</span></h2>

                        <div class="inside" style="padding: 12px;">                            
                            <form action="<?php echo TSIPBlocker\Util::getAdminPostUrl(); ?>" method="post" id="create-rule">
                                <input type="hidden" name="action" value="ts_ip_blocker_create_rule">
                                <div class="input-text-wrap">
                                    <input type="text" autocomplete="off" value="" placeholder="IP Address Query" style="width: 100%" id="ip-address-query" name="ip_address_query">
                                    <div>(e.g. 192.168.1.1 - 192.168.200.220)</div>
                                    <br>
                                </div>

                                <div class="input-text-wrap">
                                    <input type="text" autocomplete="off" value="" placeholder="Hostname Query"  style="width: 100%" id="hostname-query" name="hostname_query">
                                    <div>(e.g. *.example.com, facebook.com)</div>
                                    <br>
                                </div>

                                <div class="input-text-wrap">
                                    <input type="text" autocomplete="off" value="" placeholder="User-Agent Query" style="width: 100%" id="ua-query" name="ua_query">
                                    <div>(e.g. *.example.com, *MalwareWebsite*)</div>
                                    <br>                                
                                </div>

                                <div class="input-text-wrap">
                                    <input type="text" autocomplete="off" value="" placeholder="Referer Query" style="width: 100%" id="referer-query" name="referer_query">
                                    <div>(e.g. *.example.com, facebook.com)</div>
                                    <br>
                                </div>

                                <div class="input-text-wrap">
                                    <input type="text" autocomplete="off" value="" placeholder="Rule Name" style="width: 100%" id="rule-name" name="rule_name">
                                    <div>(e.g. Block Hackers)</div>
                                    <br>
                                </div>
                            
                                <div class="input-text-wrap">
                                    <input type="submit" class="button button-primary" value="Create" style="width: 100%">
                                </div>
                            </form>
                                
                        </div>                       
                    </div>
                </div>
            </div>

            <div id="postbox-container-2" class="postbox-container">                
                <div class="postbox">
                    <table class="wp-list-table widefat fixed striped users">
                        <thead>
                            <tr>
                                <td><strong>Name</strong></td>
                                <td><strong>IP Adress(es)</strong></td>
                                <td><strong>Hostname(s)</strong></td>
                                <td><strong>Referer</strong></td>
                                <td><strong>User-Agent</strong></td>
                                <td><strong>Block Count</strong></td>
                                <td><strong>Actions</strong></td>
                            </tr>
                        </thead>
                        <tbody id="rules-list">
                            <tr id="rule-detail-tpl" style="display:none;" class="rule-detail">
                                <td class="rule-name">-</td>
                                <td class="ip-address-query">-</td>
                                <td class="hostname-query">-</td>
                                <td class="referer-query">-</td>
                                <td class="ua-query">-</td>
                                <td class="block-count">-</td>
                                                                
                                <td>
                                    <form action="<?php echo TSIPBlocker\Util::getAdminPostUrl(); ?>" method="post" id="delete-rule">
                                        <input type="hidden" name="action" value="ts_ip_blocker_delete_rule">
                                        <input type="hidden" name="rule_id" value="">
                                        <input type="submit" class="button button-small delete" value="Delete">
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
    mixpanel.track("rules-page");
    mixpanel.track_forms("#delete-rule", "deleted-rule");
    mixpanel.track_forms("#create-rule", "created-rule");
    RulesPage.init();
});
</script>