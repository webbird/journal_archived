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

    /* toggle basic / advanced mode */
    $("input#toggle_mode").unbind("click").on("click",function(e) {
        if(confirm('Please note: Unsaved changes are lost if you click ok')) {
            var dataString = $("form[name=modify_mode]").serialize();
            $.ajax({
                type: "POST",
                url: $("form[name=modify_mode]").attr('href'),
                data: dataString,
                success: function() {
                    location.reload();
                }
    });
        } else {
        }
    });

    /* change gallery preset */
    $("select[name=gallery]").on("change",function(e) {
        if(confirm('Please note: Unsaved changes are lost if you click ok')) {
            $("form[name=modify]").submit();
        }
    });

    /* remove date */
    $("i.far.fa-calendar-times").on("click",function(e) {
        $(e.target).parent().find("input").val("");
    });

    /* drag & drop */
    jQuery('.dragdrop_item').addClass('dragdrop_handle');
    jQuery(".dragdrop_form .move_position a").remove();
    jQuery(".dragdrop_form tbody").sortable({
        appendTo:    'body',
        handle:      '.dragdrop_handle',
        opacity:     0.8,
        cursor:      'move',
        delay:       100,
        items:       'tr',
        dropOnEmpty: false,
        update: function() {
            jQuery.ajax({
                type:     'POST',
                url:      JOURNAL_MODURL +'/ajax/dragdrop.php',
                data:     jQuery(this).sortable("serialize", {
                              expression: /(.+)[:=](.+)/
                          }) + '&action=updatePosition',
                dataType: 'json',
                success:  function(json_respond){
                    if( json_respond.success != true ) {
                        alert(json_respond.message);
                    }
                }
            });
        }
    });

    /* save settings as new preset */
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