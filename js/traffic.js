window.TrafficPage = (function () {
    var ajaxObj = ts_ip_blocker_ajax_object;

    var currentPage = 0;
    var totalPages = 0;

    $ = jQuery;

    function init(opts){
        var startDate = opts["start_date"];
        var endDate = opts["end_date"];
        var datePickerOpts = {dateFormat: "yy-mm-dd"};

        $("#start-date").datepicker(datePickerOpts);
        $("#end-date").datepicker(datePickerOpts);

        $("#start-date").val(startDate);
        $("#end-date").val(endDate);

        $("#filter").click(filter);
        $("#next-page").click(nextPage);
        $("#prev-page").click(prevPage);
        loadHits();
    }

    function entryToggle(e) {
        var $t = $(e.target);
        var $group = $t.closest(".entry-group");
        var $entryDetail = $group.find(".entry-detail");
        var $angelUp = $group.find(".fa-angle-up");
        var $angelDown = $group.find(".fa-angle-down");

        if ($entryDetail.is(":hidden")) {
            $entryDetail.slideDown(200);
            $angelUp.hide();
            $angelDown.show();
        } else {
            $entryDetail.slideUp(200);
            $angelUp.show();
            $angelDown.hide();
        }
    }

    $('.entry.toggle-detail').click(entryToggle);

    function loadHits() {
        var startDate = $("#start-date").val();
        var endDate = $("#end-date").val();
        $("#hits-list tr[id!='hit-detail-tpl']").html("");

        var blockedStatus = $("#blocked-status").val();
        var pageStatus = $("#page-status").val();
        $("#spinner").show();

        var postData = {
            action: 'ts_ip_blocker_get_hits',
            page: currentPage,
            start_date: startDate,
            end_date: endDate,
            blocked_status: blockedStatus,
            page_status: pageStatus
        };

        $.post(ajaxObj.ajax_url, postData, function (resp) {
            $("#spinner").hide();
            var hits = resp['result'];
            totalPages = resp['total_pages'];
            currentPage = resp['current_page'];

            $("#total-pages").html(totalPages);
            $("#current-page").html(currentPage + 1);

            if(hits){
                for (var i = 0; i < hits.length; i++) {
                    $("#hits-list").append(hitRowHtml(hits[i]));
                }
            }            
        });
    }

    function prevPage(){
        if(currentPage > 0) {
            currentPage = currentPage - 1;
            loadHits();
        }
    }

    function nextPage(){
        if(currentPage + 1 < totalPages) {
            currentPage = currentPage + 1;
            loadHits();
        }        
    }

    function blockIp(e){
        var $btn = $(e.target);
        var ip  = $btn.closest('.hit-detail').attr("ip-address");        
        var postData = {
            action: 'ts_ip_blocker_create_rule',
            ip_address_query: ip,
            rule_name: "Block " + ip,
        };

        $.post(ajaxObj.ajax_url, postData, function (data) {
            loadHits();
        });
    }

    function hitRowHtml(hit) {
        var $tpl = $("#hit-detail-tpl").clone(true);
        $tpl.removeAttr("id");
        
        $tpl.attr("hit-id", hit["id"]);
        $tpl.attr("ip-address", hit['ip_address']);
        
        $tpl.find(".d-url").html(hit['url']);
        $tpl.find(".d-ip-address").html(hit['ip_address']);
        $tpl.find(".d-hostname").html(hit['hostname']);
        $tpl.find(".d-ua").html(hit['ua_full']);
        $tpl.find(".d-referer").html(hit['referer']);
        $tpl.find(".d-time").html(hit['created_at']);
        $tpl.find(".d-blocked").html(hit['blocked'] == 1 ? "Yes" : "No");
        $tpl.find(".d-page-not-found").html(parseInt(hit['http_not_found']) == 1 ? "Yes" : "No");

        $tpl.find(".url").html(hit['url']);
        $tpl.find(".ip-address").html(hit['ip_address']);
        $tpl.find("input[name=\"ip_address\"]").val(hit['ip_address']);

        $browser = hit['browser'];

        if ($browser == "Google Chrome") {
            $tpl.find(".browser").html("<i class='fa fa-chrome' aria-hidden='true'></i>");
        } else if ($browser == "Mozilla Firefox") {
            $tpl.find(".browser").html("<i class='fa fa-firefox' aria-hidden='true'></i>");
        } else if ($browser == "Internet Explorer") {
            $tpl.find(".browser").html("<i class='fa fa-internet-explorer' aria-hidden='true'></i>");
        } else if ($browser == "Apple Safari") {
            $tpl.find(".browser").html("<i class='fa fa-apple' aria-hidden='true'></i>");
        }

        if (hit['referer']) {
            $tpl.find(".referer").html(hit['referer']);
        }

        $tpl.find(".time").html(hit['created_at']);

        if (hit['blocked']) {
            if (parseInt(hit['blocked']) == 1) {                
                $tpl.find(".blocked").html("Yes");
                $tpl.find(".ok-icon").show();
                $tpl.find(".blocked-icon").hide();
            } else {                
                $tpl.find(".blocked").html("No");
                $tpl.find(".blocked-icon").show();
                $tpl.find(".ok-icon").hide();
            }

            if(parseInt(hit['has_blocking_rule']) == 1){
                $tpl.find(".block-ip").hide();
            }
        }

        var detailContainerId = ("hitDetail-" + hit["id"]);

        $tpl.find(".detail-container").attr("id", detailContainerId);
        $tpl.find(".view-detail-link").attr("href", "#TB_inline?width=600&height=450&inlineId=" + detailContainerId);

        $tpl.show();
        return $tpl;
    }

    function showHitDetail(e) {
        var $tr = $(e.target);
        var ipAddress = $tr.attr("ip-address");
        var hostname = $tr.attr("ip-address");
    }

    function filter(){
        loadHits();
    }

    return {
        init: init,
    }
}());
