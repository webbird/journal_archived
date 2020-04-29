jQuery.noConflict();
(function($) {
    "use strict";
     $.get(CMS_URL+"/modules/journal/js/XBTooltip.js");

    /* size presets click */
    $("span.resize_defaults").unbind("click").on("click",function(e) {
        var size = $(this).data("value");
        $("input#resize_width").val(size);
        $("input#resize_height").val(size);
    });
    $("span.resize_defaults_thumb").unbind("click").on("click",function(e) {
        var size = $(this).data("value");
        $("input#thumb_width").val(size);
        $("input#thumb_height").val(size);
    });
    $("input#toggle_mode").unbind("click").on("click",function(e) {
        $("form[name=modify_mode]").submit();
    });
    $("input[name='save_as_preset']").unbind("click").on("click",function(e) {
        e.preventDefault();
        let preset = window.prompt("Preset name");
        if (preset == null || preset == "") {
          
        } else {
            $("input[name='preset_name']").val(preset);
            $("form[name='modify']").submit();
        }
    });
})(jQuery);