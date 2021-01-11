<?php

$header = ''."\n";
$article_loop = '<div class="mod_journal_group">
    <div class="mod_journal_teaserpic">
        <a href="[LINK]">[PREVIEW_IMAGE_THUMB]</a>
    </div>
    <div class="mod_journal_teasertext">
        <a href="[LINK]"><h3>[TITLE]</h3></a>
        <div class="mod_journal_shorttext">
            [SHORT]
        </div>
        <div class="mod_journal_readmore" style="visibility:[SHOW_READ_MORE];"><a href="[LINK]">[READ_MORE]</a></div>
        <div class="mod_journal_bottom">
            <div class="mod_journal_tags">[TAGS]</div>
        </div>
    </div>
</div>
<div class="mod_journal_spacer"><hr /></div>';
$footer = '<table class="mod_journal_table" style="visibility:[DISPLAY_PREVIOUS_NEXT_LINKS]">
<tr>
    <td class="mod_journal_table_left">[PREVIOUS_PAGE_LINK]</td>
    <td class="mod_journal_table_center">[OF]</td>
    <td class="mod_journal_table_right">[NEXT_PAGE_LINK]</td>
</tr>
</table>';
$article_header = '<h2>[TITLE]</h2>';
$article_content = '<div class="mod_journal_content_short">
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