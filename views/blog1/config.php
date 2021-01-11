<?php

$header = '<div class="mod_journal_blog1_wrapper">';
$article_loop = '<article class="article">
    <div>
        <div class="mod_journal_article_image">
            <a href="[LINK]">[PREVIEW_IMAGE_THUMB]</a>
        </div>
        <div class="mod_journal_article_bind">
            <div class="mod_journal_article_header">
                <div class="mod_journal_article_tag">
                    [TAGS]
                </div>
                <h2>
                    <span>
                        <a href="[LINK]">[TITLE]</a>
                    </span>
                </h2>
            </div>
            <div class="mod_journal_article_entry">[SHORT]</div>
        </div>
        <div class="mod_journal_readmore" style="visibility:[SHOW_READ_MORE];"><a href="[LINK]">[READ_MORE]</a></div>
    </div>
</article>';
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
<div class="mod_journal_metadata">[COMPOSED_BY] [DISPLAY_NAME] [ON] [ARTICLE_DATE] [AT] [ARTICLE_TIME] [O_CLOCK] | [LAST_MODIFIED_BY] [MODI_BY] [ON] [MODI_DATE] [AT] [MODI_TIME] [O_CLOCK]</div>';
$article_content = '<div class="mod_journal_content_short">
  [CONTENT_SHORT]
</div>
<div class="mod_journal_content_long">[CONTENT_LONG]</div>
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