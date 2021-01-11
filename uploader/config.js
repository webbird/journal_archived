//jQuery.noConflict();
(function($) {
  /*
   * For the sake keeping the code clean and the examples simple this file
   * contains only the plugin configuration & callbacks.
   * 
   * UI functions ui_* can be located in: ui.js
   */
  $('#drag-and-drop-zone').dmUploader({
    url: JOURNAL_UPLOAD_URL,
    //maxFileSize: JOURNAL_IMAGE_MAX_SIZE,
    allowedTypes: "image/*", 
    onDragEnter: function(){
      // Happens when dragging something over the DnD area
      this.addClass('active');
    },
    onDragLeave: function(){
      // Happens when dragging something OUT of the DnD area
      this.removeClass('active');
    },
    onComplete: function(){
      // All files in the queue are processed (success or error)
      this.ui_add_status(JOURNAL_COMPLETE_MESSAGE);
    },
    onNewFile: function(id, file){
      // When a new file is added using the file selector or the DnD area
      this.ui_multi_add_file(id, file);
      if (typeof FileReader !== "undefined"){
        var reader = new FileReader();
        var img = $('#uploaderFile' + id).find('img');

        reader.onload = function (e) {
          img.attr('src', e.target.result);
        }
        reader.readAsDataURL(file);
      }
    },
    onBeforeUpload: function(id){
      // about tho start uploading a file
      this.ui_multi_update_file_status(id, 'uploading', 'Uploading...');
      this.ui_multi_update_file_progress(id, 0, '', true);
    },
    onUploadCanceled: function(id) {
      // Happens when a file is directly canceled by the user.
      this.ui_multi_update_file_status(id, 'warning', 'Canceled by User');
      this.ui_multi_update_file_progress(id, 0, 'warning', false);
    },
    onUploadProgress: function(id, percent){
      // Updating file progress
      this.ui_multi_update_file_progress(id, percent);
    },
    onUploadSuccess: function(id, data){
//console.log(id,data,DmUploader.findById(id));
      if(data.status == 'error') {
        this.ui_multi_update_file_status(id, 'danger', data.message);
        this.ui_multi_update_file_progress(id, 0, 'danger', false);
      } else {
        this.ui_multi_update_file_status(id, 'success', 'Upload Complete');
        this.ui_multi_update_file_progress(id, 100, 'success', false);
      }
    },
    onUploadError: function(id, xhr, status, message){
      this.ui_multi_update_file_status(id, 'danger', message);
      this.ui_multi_update_file_progress(id, 0, 'danger', false);
    },
    onFileSizeError: function(file){
      this.ui_add_status(JOURNAL_SIZE_MESSAGE + ": " + file.name, 'danger');
    }
  });
})(jQuery);
