window.SettingsPage = (function () {
    var ajaxObj = ts_ip_blocker_ajax_object;
    var $ = jQuery;

    function init() {        
        var $toggle = $("#adv-settings-toggle");
        $toggle.click(function(){
            var $section = $("#adv-settings-section");
            if($section.is(":hidden")){
                $section.show();
                $toggle.html("Hide Advanced Settings");
            }else{
                $section.hide();
                $toggle.html("Show Advanced Settings");
            }
        });
    }

    return {
        init: init
    }
})();