<script src="<?php echo CMSBRIDGE_CMS_URL.'/modules/'.JOURNAL_MODDIR.'/js/galleries/' ?>masonry/masonry.min.js"></script>
<script src="<?php echo CMSBRIDGE_CMS_URL.'/modules/'.JOURNAL_MODDIR.'/js/galleries/' ?>masonry/imagesLoaded.min.js"></script>

<script>
    var $grid = $('.masonry-grid').imagesLoaded( function() {
      $grid.masonry({
        itemSelector: '.masonry-grid-item',
        percentPosition: true,
        columnWidth: '.masonry-grid-sizer'
      });
    });
</script>