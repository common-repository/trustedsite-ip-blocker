window.RulesPage = (function () {
    var ajaxObj = ts_ip_blocker_ajax_object;

    var $ = jQuery;

    function init() {
        console.log("RulesPage.init!");
        loadRulesList();
    }

    function loadRulesList() {
        $("#rules-list tr[id!='rule-detail-tpl']").remove();
        
        $.post(ajaxObj.ajax_url, {action: 'ts_ip_blocker_get_rules'}, function (data) {
            var rules = data['rules'];
            for (var i = 0; i < rules.length; i++) {
                $("#rules-list").append(ruleDetailHtml(rules[i]));
            }
        });
    }

    function deleteRule(e) {
        var $t = $(e.target);
        var ruleId = $t.closest(".rule-detail").attr("rule-id");
        $.post(ajaxObj.ajax_url, {action: 'ts_ip_blocker_delete_rule', rule_id: ruleId}, function (data) {
            loadRulesList();
        });
    }

    function ruleDetailHtml(rule) {
        var $tpl = $("#rule-detail-tpl").clone(true);

        $tpl.removeAttr("id");
        $tpl.attr("rule-id", rule["id"]);
        $tpl.find("input[name=\"rule_id\"]").val(rule["id"]);
        $tpl.find(".rule-name").html(rule['rule_name']);
        $tpl.find(".block-count").html(rule['block_count']);

        if (rule['ip_address_query']) {
            $tpl.find(".ip-address-query").html(rule['ip_address_query']);
        }

        if (rule['hostname_query']) {
            $tpl.find(".hostname-query").html(rule['hostname_query']);
        }

        if (rule['ua_query']) {
            $tpl.find(".ua-query").html(rule['ua_query']);
        }

        if (rule['referer_query']) {
            $tpl.find(".referer-query").html(rule['referer_query']);
        }

        $tpl.find(".delete").click(deleteRule);
        $tpl.show();

        return $tpl;
    }

    return {
        init: init
    }
}());