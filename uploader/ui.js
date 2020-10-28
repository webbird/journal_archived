/**
 * Some helper functions to work with our UI and keep our code cleaner
 **/
//jQuery.noConflict();
(function($) {
    // Adds an entry to our status area
    $.fn.ui_add_status = function(message, color)
    {
      var template = $('#status-template').text();
      template = template.replace('%%message%%', message);
      $('#status').find('li').fadeOut(); // remove any previous status
      $('#status').prepend(template);
      if (typeof color != 'undefined'){
        $('#status > li').removeClass('bg-success bg-info bg-warning bg-danger');
        $('#status > li').addClass('bg-' + color);
      }
    }

    // Creates a new file and add it to our list
    $.fn.ui_multi_add_file = function (id, file)
    {
      var template = $('#files-template').text();
      template = template.replace('%%filename%%', file.name);
      template = $(template);
      template.prop('id', 'uploaderFile' + id);
      template.data('file-id', id);
      $('#files').find('li.empty').fadeOut(); // remove the 'no files yet'
      $('#files').prepend(template);
    }

    // Changes the status messages on our list
    $.fn.ui_multi_update_file_status = function(id, status, message)
    {
      $('#uploaderFile' + id).find('span').html(message).prop('class', 'status text-' + status);
    }

    // Updates a file progress, depending on the parameters it may animate it or change the color.
    $.fn.ui_multi_update_file_progress = function(id, percent, color, active)
    {
      color = (typeof color === 'undefined' ? false : color);
      active = (typeof active === 'undefined' ? true : active);

      var bar = $('#uploaderFile' + id).find('div.progress-bar');

      bar.width(percent + '%').attr('aria-valuenow', percent);
      bar.toggleClass('progress-bar-striped progress-bar-animated', active);

      if (percent === 0){
        bar.html('');
      } else {
        bar.html(percent + '%');
      }

      if (color !== false){
        bar.removeClass('bg-success bg-info bg-warning bg-danger');
        bar.addClass('bg-' + color);
      }
    }
})(jQuery);