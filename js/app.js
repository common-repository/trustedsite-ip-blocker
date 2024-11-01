window.IPBlockerApp = (function () {
    var ajaxObj = ts_ip_blocker_ajax_object;

    var Pages = {};

    function init() {
        console.log("IPBlockerApp.init");

        Pages.Hits = jQuery('#hits-page');
        Pages.Rules = jQuery('#rules-page');

        jQuery("#hits-button").click(function () {
            loadHitsPage(function () {
                Pages.Hits.show();
                Pages.Rules.hide();
            });
        });

        jQuery("#rules-button").click(function () {
            loadRulesPage(function () {
                Pages.Rules.show();
                Pages.Hits.hide();
            });
        });

        jQuery("#create-rule").click(createRule);

        loadHitsPage(function () {
            Pages.Hits.show();
        });
    }

    function createRule() {
        var $operator = jQuery("select#operator");
        var $ip1 = jQuery("input#ip_address1");
        var $ip2 = jQuery("input#ip_address2");
        var $hostname = jQuery("input#hostname");
        var $referer = jQuery("input#referer");
        var $ua = jQuery("input#ua");
        var $comment = jQuery("input#comment");

        var postData = {
            action: 'ts_ip_blocker_create_rule',
            operator: $operator.val(),
            ip_address1: $ip1.val(),
            ip_address2: $ip2.val(),
            hostname: $hostname.val(),
            referer: $referer.val(),
            ua: $ua.val(),
            comment: $comment.val()
        };

        jQuery.post(ajaxObj.ajax_url, postData, function (data) {
            var errors = data.errors;

            if (errors) {

            } else {
                var rule = data.rule;
            }
        });
    }

    function loadRulesPage(callback) {
        fetchRules(function (data) {
            var $rulesTbody = jQuery("#rules-table tbody");
            $rulesTbody.html("");

            var rules = data.rules;

            for (var i = 0; i < rules.length; i++) {

                var d = rules[i];
                var $tr = jQuery("<tr data-rule-id='" + d["id"] + "'></tr>");
                $tr.append(jQuery("<td>" + d["operator"] + "</td>"));
                $tr.append(jQuery("<td>" + d["ip_address1"] + "</td>"));
                $tr.append(jQuery("<td>" + d["ip_address2"] + "</td>"));
                $tr.append(jQuery("<td>" + d["hostname"] + "</td>"));
                $tr.append(jQuery("<td>" + d["referer"] + "</td>"));
                $tr.append(jQuery("<td>" + d["ua"] + "</td>"));

                $tr.append(jQuery("<td>" + d["comment"] + "</td>"));
                var $actionsTd = jQuery("<td><a class='delete-rule'>Delete</a></td>");

                $tr.append($actionsTd);
                $rulesTbody.append($tr);
            }

            jQuery(".delete-rule").click(function(e){
                var $el = jQuery(e.target).closest('tr');
                deleteRule($el.data("rule-id"), function(){
                    $el.remove();
                })
            });

            if (callback) {
                callback();
            }
        });
    }

    function deleteRule(ruleId, callback){
        jQuery.post(ajaxObj.ajax_url, {action: 'ts_ip_blocker_delete_rule', id: ruleId}, callback);
    }

    function loadHitsPage(callback) {
        fetchHits(function (data) {
            var $hitsTbody = jQuery("#hits-table tbody");
            var hits = data.hits;

            for (var i = 0; i < hits.length; i++) {
                var $tr = jQuery("<tr></tr>");
                var d = hits[i];
                var country = !!d.country ? d.country : "";
                var city = !!d.city ? d.city : "";

                var location = country; //TODO: Country, City
                if(!!city && !!country){
                    location = (country + "," + city);
                }

                $tr.append(jQuery("<td>" + d["url"] + "</td>"));
                $tr.append(jQuery("<td>" + d["referer"] + "</td>"));
                $tr.append(jQuery("<td>" + location + "</td>"));
                $tr.append(jQuery("<td>" + d["ip_address"] + "</td>"));
                $tr.append(jQuery("<td>" + d["hostname"] + "</td>"));
                $tr.append(jQuery("<td>" + d["ua"] + "</td>"));
                $tr.append(jQuery("<td>" + d["blocked"] + "</td>"));
                $tr.append(jQuery("<td></td>"));

                $hitsTbody.append($tr);
            }

            if (callback) {
                callback();
            }
        });

    }

    function fetchRules(callback) {
        jQuery.post(ajaxObj.ajax_url, {action: 'ts_ip_blocker_get_rules'}, callback);
    }

    function fetchHits(callback) {
        jQuery.post(ajaxObj.ajax_url, {action: 'ts_ip_blocker_get_hits'}, callback);
    }

    return {
        init: init,
        fetchHits: fetchHits,
        fetchRules: fetchRules
    }
})();