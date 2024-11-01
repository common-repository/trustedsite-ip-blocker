window.OverviewPage = (function(){
    var ajaxObj = ts_ip_blocker_ajax_object;
    var $ = jQuery;

    function init(opts){
        var startDate = opts["start_date"];
        var endDate = opts["end_date"];
        var datePickerOpts = {dateFormat: "yy-mm-dd"};

        $("#start-date").datepicker(datePickerOpts);
        $("#end-date").datepicker(datePickerOpts);
        $("#start-date").val(startDate);
        $("#end-date").val(endDate);

        dailyTrafficChart();
        acceptedVsBlockedChart();
        trafficByRefererChart();
        rulesBlockCountsChart();
    }

    function trafficByIpChart() {
        var startDate = $("#start-date").val();
        var endDate = $("#end-date").val();
        var postData = {action: 'ts_ip_blocker_get_hits_count_per_ip', start_date: startDate, end_date: endDate};

        $.post(ajaxObj.ajax_url, postData, function (response) {
            var respData = response['data'];
            var labels = [];
            var data = [];
            for(var i = 0; i < respData.length; i ++){
                var d = respData[i];
                labels.push(d["ip_address"]);
                data.push(parseInt(d["hits_count"]));
            }

            var data = {
                labels: labels,
                datasets: [                    
                    { 
                        data: data,
                        backgroundColor: ["#FFCF80","#9B9B9B","#E2FFC1","#A2CDFF", "#F9DCFF"],
                    }
                ]
            };

            var chart = new Chart(document.getElementById('traffic-per-ip').getContext('2d'),{
                type: 'pie',
                data: data,
                options: {}
            });
        });
    }

    function rulesBlockCountsChart() {
        $.post(ajaxObj.ajax_url, {action: 'ts_ip_blocker_get_rules_block_counts'}, function (response) {
            var respData = response['data'];
            var labels = [];
            var data = [];

            for(var i = 0; i < respData.length; i ++){
                var d = respData[i];
                labels.push(d["rule_name"]);
                data.push(parseInt(d["block_count"]));
            }

            var chartData = {
                labels: labels,
                datasets: [
                    {
                        data: data,
                        backgroundColor: ["#FFCF80","#9B9B9B","#E2FFC1","#A2CDFF", "#F9DCFF"],
                    }
                ]
            };

            var chart = new Chart(document.getElementById('rules-block-counts').getContext('2d'),{
                type: 'pie',
                data: chartData,
                options: {}
            });
        });
    }

    function trafficByRefererChart(){
        var startDate = $("#start-date").val();
        var endDate = $("#end-date").val();
        var postData = {action: 'ts_ip_blocker_get_hits_count_per_referer', start_date: startDate, end_date: endDate};

        $.post(ajaxObj.ajax_url, postData, function (response) {
            var respData = response['data'];
            var labels = [];
            var data = [];

            for(var i = 0; i < respData.length; i ++){
                var d = respData[i];
                var referer = d["referer"];
                if(referer === ""){
                    labels.push("Direct");
                }else{
                    labels.push(referer)
                }

                data.push(parseInt(d["hits_count"]));
            }

            var chartData = {
                labels: labels,
                datasets: [                    
                    { 
                        data: data,
                        backgroundColor: ["#FFCF80","#9B9B9B","#E2FFC1","#A2CDFF", "#F9DCFF"],
                    }
                ]
            };

            var chart = new Chart(document.getElementById('traffic-per-referer').getContext('2d'),{
                type: 'pie',
                data: chartData,
                options: {}
            });
        });
    }

    function acceptedVsBlockedChart(){
        var startDate = $("#start-date").val();
        var endDate = $("#end-date").val();
        var postData = {action: 'ts_ip_blocker_get_hits_count_per_group', start_date: startDate, end_date: endDate};

        $.post(ajaxObj.ajax_url, postData, function (response) {
            var respData = response['data'];
            var data = [respData['accepted_count'], respData['block_count']];

            var chartData = {
                labels: [
                    "Accepted",
                    "Blocked",
                ],

                datasets: [
                    {
                        data: data,
                        backgroundColor: [
                            "rgba(234, 235, 235, 1.0)",
                            "rgba(255, 208, 201, 1.0)"
                        ],
                    }]
            };

            var chart = new Chart(document.getElementById('accepted-vs-blocked').getContext('2d'),{
                type: 'pie',
                data: chartData,
                options: {}
            });
        });
    }

    function dailyTrafficChart(){
        var startDate = $("#start-date").val();
        var endDate = $("#end-date").val();
        var postData = {action: 'ts_ip_blocker_get_daily_hits_count', start_date: startDate, end_date: endDate};

        $.post(ajaxObj.ajax_url, postData, function (response) {
            var hitsData = response['data'];
            var labels = [];
            var blockedCount = [];
            var hitsCount = [];

            for(var i = 0; i < hitsData.length; i ++){
                var createdDate = (new Date(hitsData[i]["created_at"]));
                labels.push((createdDate.getMonth() + 1) + "/" + (createdDate.getDate() +1) + "/" + createdDate.getFullYear());
                blockedCount.push(hitsData[i]["block_count"]);
                hitsCount.push(hitsData[i]["hits_count"]);
            }

            var data = {
                labels: labels,
                datasets: [
                    {
                        label: 'Visits',
                        lineTension: 0,
                        data: hitsCount,
                        borderColor: "#ADAFAE",
                    },
                    {
                        label: 'Blocked',
                        lineTension: 0,
                        borderColor: "#FE4327",
                        backgroundColor: "rgba(255, 208, 201, 0.6)",
                        data: blockedCount
                    }
                ]
            };

            var chart = new Chart(document.getElementById('traffic-chart').getContext('2d'), {
                type: 'line',
                data: data,
                options: {
                    legend: {
                        display: false
                    }
                }
            });
        });
    }

    function loadMostBlockedIps(){
        var $mbr = $("#most-blocked-ips");

        $.post(ajaxObj.ajax_url, {action: 'ts_ip_blocker_get_most_blocked_ips'}, function (resp) {
            var data = resp['data'];
            var i = 0;
            for (; i < data.length; i++) {
                var d = data[i];
                var $tpl = $("#top-rule-entry-tpl").clone(true);
                $tpl.removeAttr("id");
                $tpl.find(".rule-name").html(d['ip_address']);
                $tpl.find(".block-count").html(d['block_count']);
                $tpl.show();
                $mbr.append($tpl);
            }
            while(i < 3){
                var $tpl = $("#top-rule-entry-tpl").clone(true);
                $tpl.removeAttr("id");
                $tpl.find(".rule-name").html("&nbsp;");
                $tpl.find(".block-count").html("&nbsp;");
                $tpl.show();
                $mbr.append($tpl);
                i++;
            }
        });
    }


    function loadMostBlockingRules() {
        var $mbr = $("#most-blocking-rules");
        $mbr.html("");

        $.post(ajaxObj.ajax_url, {action: 'ts_ip_blocker_get_most_blocking_rules'}, function (resp) {
            var rules = resp['rules'];
            var i = 0;
            for (; i < rules.length; i++) {
                $mbr.append(ruleRowHtml(rules[i]));
            }

            while(i < 3){
                var $tpl = $("#top-rule-entry-tpl").clone(true);
                $tpl.removeAttr("id");
                $tpl.find(".rule-name").html("&nbsp;");
                $tpl.find(".block-count").html("&nbsp;");
                $tpl.show();
                $mbr.append($tpl);
                i++;
            }
        });
    }

    function ruleRowHtml(rule){
        var $tpl = $("#top-rule-entry-tpl").clone(true);

        $tpl.removeAttr("id");
        $tpl.data("rule-id", rule["id"]);
        $tpl.find(".rule-name").html(rule['rule_name']);
        $tpl.find(".block-count").html(rule['block_count']);
        $tpl.show();

        return $tpl;
    }

    return {
        init: init
    }
})();




