<?php

$header = ''."\n";
$article_loop = '<div class="mod_journal_group">
    <div class="mod_journal_teaserpic">
        <a href="[LINK]">[PREVIEW_IMAGE]</a>
    </div>
    <div class="mod_journal_teasertext">
        <a href="[LINK]"><h3>[TITLE]</h3></a>
        <div class="mod_journal_shorttext">
            [SHORT]
        </div>
        <div class="mod_journal_readmore" style="visibility:[SHOW_READ_MORE];"><a href="[LINK]">[TEXT_READ_MORE]</a></div>
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
$article_header = addslashes('<h2>[TITLE]</h2>');
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
    <td class="mod_journal_table_center"><a href="[BACK]">[TEXT_BACK]</a></td>
    <td class="mod_journal_table_right">[NEXT_PAGE_LINK]</td>
</tr>
</table>
<div class="mod_journal_tags">[TAGS]</div>';