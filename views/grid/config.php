<?php

$header = '<div class="mod_journal_grid_wrapper mod_journal_grid_2columns">';
$article_loop = '<section class="mod_journal_grid_box">
    <div class="mod_journal_grid_wrapper mod_journal_grid_5columns">
        <div class="mod_journal_teaserpic mod_journal_grid_box">
            <a href="[LINK]">[PREVIEW_IMAGE]</a>
        </div>
        <div class="mod_journal_teasertext mod_journal_grid_box" style="grid-column:2/6">
            <a href="[LINK]"><h3>[TITLE]</h3></a>
            <div class="mod_journal_metadata">[COMPOSED_BY] [DISPLAY_NAME] [ON] [ARTICLE_DATE] [AT] [ARTICLE_TIME] [O_CLOCK] [IN_GROUP] [GROUP_TITLE]</div>
            <div class="mod_journal_tags">[TAGS]</div>
        </div>
    </div>
    <div class="mod_journal_shorttext">
        [SHORT]
    </div>
    <div class="mod_journal_bottom">
        <div class="mod_journal_readmore" style="visibility:[SHOW_READ_MORE];"><a href="[LINK]">[READ_MORE]</a></div>
    </div>
</section>';
$footer = '</div>
<div class="mod_journal_spacer"></div>
<table class="mod_journal_table">
<tr>
    <td class="mod_journal_table_left">[PREVIOUS_PAGE_LINK]</td>
    <td class="mod_journal_table_center">&nbsp;</td>
    <td class="mod_journal_table_right">[NEXT_PAGE_LINK]</td>
</tr>
</table>';
$article_header = '<h2>[TITLE]</h2>
<div class="mod_journal_metadata">[COMPOSED_BY] [DISPLAY_NAME] [ON] [ARTICLE_DATE] [AT] [ARTICLE_TIME] [O_CLOCK] | [MODIFIED] [MODI_DATE] [AT] [MODI_TIME] [O_CLOCK]</div>';
$article_content = '<div class="mod_journal_content_short">
  [IMAGE]
  [CONTENT_SHORT]
</div>
<div class="mod_journal_content_long">[CONTENT_LONG]</div>
<div class="fotorama" data-keyboard="true" data-navposition="top" data-nav="thumbs">
[IMAGES]
</div>
';
$article_footer = ' <div class="mod_journal_spacer"></div>
<table class="mod_journal_table" style="visibility: [DISPLAY_PREVIOUS_NEXT_LINKS]">
<tr>
    <td class="mod_journal_table_left">[PREVIOUS_PAGE_LINK]</td>
    <td class="mod_journal_table_center"><a href="[BACK_LINK]">[BACK]</a></td>
    <td class="mod_journal_table_right">[NEXT_PAGE_LINK]</td>
</tr>
</table>
<div class="mod_journal_tags">[TAGS]</div>';
$tag_loop = '<a href="[TAG_LINK]" class="mod_journal_tag" id="mod_journal_tag_[PAGEID]_[TAGID]" style="background-color:[TAGCOLOR];color:[TEXTCOLOR]" onmouseover="this.style.backgroundColor=\'[TAGHOVERCOLOR]\';" onmouseout="this.style.backgroundColor=\'[TAGCOLOR]\';this.style.color=\'[TEXTHOVERCOLOR];return true;\'">[TAG]</a>';