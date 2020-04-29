<script src="<?php echo WB_URL ?>/modules/journal/js/galleries/masonry/masonry.min.js"></script>
<script src="<?php echo WB_URL ?>/modules/journal/js/galleries/masonry/imagesLoaded.min.js"></script>

<script>
    $.include(WB_URL+"/modules/journal/js/galleries/masonry/masonry.css");
    var $grid = $('.masonry-grid').imagesLoaded( function() {
      $grid.masonry({
        itemSelector: '.masonry-grid-item',
        percentPosition: true,
        columnWidth: '.masonry-grid-sizer'
      });
    });
</script>