<?php

/*
   ____  __      __    ___  _  _  ___    __   ____     ___  __  __  ___
  (  _ \(  )    /__\  / __)( )/ )/ __)  /__\ (_  _)   / __)(  \/  )/ __)
   ) _ < )(__  /(__)\( (__  )  (( (__  /(__)\  )(    ( (__  )    ( \__ \
  (____/(____)(__)(__)\___)(_)\_)\___)(__)(__)(__)    \___)(_/\/\_)(___/

   @author          BlackBird Webprogrammierung
   @copyright       2020 BlackBird Webprogrammierung
   @link            https://www.webbird.de
   @license         http://www.gnu.org/licenses/gpl.html
   @package         journal

   Note: This is a crossover module that is indented to run with WBCE,
   BlackCat CMS v1.x and BlackCat CMS v2.x. There are some concessions to
   make this work.

*/

namespace CAT\Addon\Journal\Importer;

if(!class_exists('topicsImport',false))
{
    final class topicsImport
    {
        public static function import(int $sourceID, int $targetID)
        {
            // find the module name
            $stmt = \CAT\Addon\cmsbridge::db()->query(sprintf(
                'SELECT `module` FROM `%ssections` WHERE `section_id`=%d',
                \CAT\Addon\cmsbridge::dbprefix(), $sourceID
            ));
            $row = $stmt->fetch();
            $module_name = $row['module'];

            // import settings
            $topics_settings = self::importSettings($sourceID, $targetID, $module_name);

            // import topics
            self::importTopics($sourceID, $targetID, $module_name);

        }

        /**
         *
         * @access protected
         * @return
         **/
        protected static function getColumnMap(?string $table='settings')
        {
            if($table == 'settings') {
                return array(
                    'view_order'        => 'sort_topics',
                    'articles_per_page' => 'topics_per_page',
                    'header'            => 'header',
                    'article_loop'      => 'topics_loop',
                    'footer'            => 'footer',
                    'block2'            => 'topic_block2',
                    'article_header'    => 'topic_header',
                    'article_footer'    => 'topic_footer',
                );
            }
            if($table == 'topics') {
                return array(
                    'active'            => 'active',
                    'published_when'    => 'published_when',
                    'published_until'   => 'published_until',
                    'title'             => 'title',
                    'content_short'     => 'content_short',
                    'content_long'      => 'content_long',
                    'posted_when'       => 'posted_first',
                    'posted_by'         => 'posted_by',
                    'modified_when'     => 'posted_modified',
                    'modified_by'       => 'modified_by',
                    'link'              => 'link',
                );
            }
        }   // end function getColumnMap()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function getPlaceholderMap()
        {
            return array(
                '[ACTIVE]'                        => null,
                '[ADDITIONAL_PICTURES]'           => null,
                '[ALLCOMMENTSLIST]'               => null,
                '[CLASSES]'                       => null,
                '[COMMENTFRAME]'                  => null,
                '[COMMENT_ID]'                    => null,
                '[COMMENTSCLASS]'                 => null,
                '[COMMENTSCOUNT]'                 => null,
                '[CONTENT_EXTRA]'                 => null,
                '[CONTENT_LONG]'                  => '[CONTENT_LONG]',
                '[CONTENT_LONG_FIRST]'            => '[CONTENT_LONG]',
                '[COUNTER]'                       => '[NUMBER]',
                '[COUNTER2]'                      => null,
                '[COUNTER3]'                      => null,
                '[EDITLINK]'                      => null,
                '[EVENT_START_DATE]'              => null,
                '[EVENT_START_DAY]'               => null,
                '[EVENT_START_DAYNAME]'           => null,
                '[EVENT_START_MONTH]'             => null,
                '[EVENT_START_MONTHNAME]'         => null,
                '[EVENT_START_TIME]'              => null,
                '[EVENT_START_YEAR]'              => null,
                '[EVENT_STOP_DATE]'               => null,
                '[EVENT_STOP_DAY]'                => null,
                '[EVENT_STOP_DAYNAME]'            => null,
                '[EVENT_STOP_MONTH]'              => null,
                '[EVENT_STOP_MONTHNAME]'          => null,
                '[EVENT_STOP_TIME]'               => null,
                '[EVENT_STOP_YEAR]'               => null,
                '{FULL_TOPICS_LIST}'              => null,
                '{JUMP_LINKS_LIST}'               => null,
                '[HREF]'                          => null,
                '[META_DESCRIPTION]'              => null,
                '[META_KEYWORDS]'                 => null,
                '{NAME}'                          => null,
                '[PICTURE]'                       => '[PREVIEW_IMAGE]',
                '{PICTURE}'                       => '[PREVIEW_IMAGE]',
                '{PREV_NEXT_PAGES}'               => null,
                '[PICTURE_DIR]'                   => null,
                '[PUBL_DATE]'                     => '[PUBLISHED_DATE]',
                '[PUBL_TIME]'                     => '[PUBLISHED_TIME]',
                '[SECTION_DESCRIPTION]'           => null,
                '[SECTION_ID]'                    => null,
                '[SECTION_TITLE]'                 => null,
                '[READ_MORE]'                     => null,
                '{SEE_ALSO}'                      => null,
                '{SEE_PREVNEXT}'                  => null,
                '[SHORT_DESCRIPTION]'             => null,
                '{THUMB}'                         => '<a href="[LINK]">[PREVIEW_IMAGE_THUMB]</a>',
                '[THUMB]'                         => '[PREVIEW_IMAGE_THUMB]',
                '{TITLE}'                         => '<a href="[LINK]">[TITLE]</a>',
                '[TOPIC_ID]'                      => null,
                '[TOPIC_EXTRA]'                   => null,
                '[TOPIC_SCORE]'                   => null,
                '[TOPIC_SHORT]'                   => '[CONTENT_SHORT]',
                '[TOTALNUM]'                      => null,
                '[USER_EMAIL]'                    => '[EMAIL]',
                '[USER_NAME]'                     => '[USERNAME]',
                '[USER_DISPLAY_NAME]'             => '[DISPLAY_NAME]',
                '[USER_MODIFIEDINFO]'             => null,
                '[XTRA1]'                         => null,
                '[XTRA2]'                         => null,
                '[XTRA3]'                         => null,
            );
        }   // end function getPlaceholderMap()
        
        /**
         *
         * @access protected
         * @return
         **/
        protected static function getTopicsSettings(int $sourceID, string $module_name)
        {
            // fetch settings
            $stmt = \CAT\Addon\cmsbridge::db()->query(sprintf(
                'SELECT * FROM `%smod_%s_settings` WHERE `section_id`=%d',
                \CAT\Addon\cmsbridge::dbprefix(), $module_name, $sourceID
            ));
            $settings = $stmt->fetch();
            return $settings;
        }   // end function getTopicsSettings()
        

        /**
         *
         * @access protected
         * @return
         **/
        protected static function importSettings(int $sourceID, int $targetID, string $module_name)
        {
            $settings = self::getTopicsSettings($sourceID, $module_name);
            if(isset($_REQUEST['include_settings']))
            {
                $qb = \CAT\Addon\cmsbridge::conn()->createQueryBuilder();
                $c  = $qb->getConnection();

                // map view order
                $view_order = 0; // by position
                // 1 = pub. date, 3 = Eventkalender
                if (($settings['sort_topics']==1) || ($settings['sort_topics']==3)) {
                    $view_order = $settings['sort_topics'];
                }

                // map placeholders
                $placeholdermap = self::getPlaceholderMap();
                $settings = str_replace(array_keys($placeholdermap), array_values($placeholdermap), $settings);

                // current settings for Journal section
                $journal_settings = \CAT\Addon\Journal::getSettingsForSection($targetID);

                // column mapping
                $column_map = self::getColumnMap();

                // update settings
                $qb->update(sprintf('%s%s',\CAT\Addon\cmsbridge::dbprefix(), 'mod_journal_settings'))
                   ->where('`section_id`='.$targetID);

                foreach($journal_settings as $key => $val) {
                    if(isset($column_map[$key])) {
                        $val = $settings[$column_map[$key]];
                        if(!in_array($key,array('view_order','articles_per_page'))) {
                            $val = $c->quote($val);
                        }
                        $qb->set($c->quoteIdentifier($key),$val);
                    }
                }

                // make block2 visible if there is some content
                if(isset($settings['topic_block2']) && !empty($settings['topic_block2'])) {
                    $qb->set($c->quoteIdentifier('use_second_block'),$c->quote('Y'));
                }

                $qb->set($c->quoteIdentifier('view'), $c->quote('topics_'.$sourceID));
                $qb->execute();

                // create a new view by importing the css file
                mkdir(JOURNAL_DIR.'/views/topics_'.$sourceID, 0770);
                $file = JOURNAL_DIR.'/views/topics_'.$sourceID.'/frontend.css';
                copy(CMSBRIDGE_CMS_PATH.'/modules/'.$module_name.'/frontend.css',$file);

                // replace mod_topic with mod_journal in frontend.css
                //file_put_contents($file, str_replace("mod_topic", "mod_journal", file_get_contents($file)));
            }

            return $settings;
        }   // end function importSettings()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function importTags(int $sectionID, int $articleID, array $keywords)
        {
            // get already available tags
            $tags = \CAT\Addon\Journal::getTags($sectionID,true);
            // iterate keywords so see which are already there
            foreach($keywords as $word) {
                $seen = false;
                foreach($tags as $i => $tag) {
                    if(strcasecmp($tag['tag'],$word)==0) {
                        $seen = $tag['tag_id'];
                    }
                }
                if(!$seen) {
                    // add as new tag
                    \CAT\Addon\cmsbridge::db()->query(sprintf(
                        'INSERT IGNORE INTO `%s%s` '.
                        '(`tag`) VALUES '.
                        '( "%s" )',
                        \CAT\Addon\cmsbridge::dbprefix(),
                        'mod_journal_tags',
                        $word
                    ));
                    $seen = \CAT\Addon\cmsbridge::getLastInsertId();
                    // link tag to section
                    \CAT\Addon\cmsbridge::db()->query(sprintf(
                        'INSERT IGNORE INTO `%s%s` '.
                        '(`section_id`, `tag_id`) VALUES '.
                        '( %d         , %d  )',
                        \CAT\Addon\cmsbridge::dbprefix(),
                        'mod_journal_tags_sections',
                        $sectionID,
                        intval($seen)
                    ));
                }
                // link tag to article
                \CAT\Addon\cmsbridge::db()->query(sprintf(
                    'INSERT IGNORE INTO `%s%s` '.
                    '(`article_id`, `tag_id`) VALUES '.
                    '(%d          , %d      )',
                    \CAT\Addon\cmsbridge::dbprefix(),
                    'mod_journal_tags_articles',
                    $articleID, intval($seen)
                ));
            }
        }   // end function importTags()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function importTopics(int $sourceID, int $targetID, string $module_name)
        {
            $qb = \CAT\Addon\cmsbridge::conn()->createQueryBuilder();
            $c  = $qb->getConnection();

            // get settings
            $settings = self::getTopicsSettings($sourceID, $module_name);

            // get topics
            $stmt = \CAT\Addon\cmsbridge::db()->query(sprintf(
                'SELECT * FROM `%smod_%s` WHERE `section_id`=%d ORDER BY `topic_id`',
                \CAT\Addon\cmsbridge::dbprefix(), $module_name, $sourceID
            ));
            if(is_object($stmt) && $stmt->rowCount()>0) {
                $pageID = \CAT\Addon\cmsbridge::getPageForSection($targetID);
                $pg = \CAT\Addon\cmsbridge::getPage($pageID);
                $dir = WB_PATH.PAGES_DIRECTORY.$pg['link'].'/';
                $colmap = self::getColumnMap('topics');
                while($row = $stmt->fetch()) {
                    $qb->resetQueryParts()
                       ->insert(sprintf('%s%s',\CAT\Addon\cmsbridge::dbprefix(), 'mod_journal_articles'))
                       ->setValue($c->quoteIdentifier('section_id'), $targetID)
                       ->setValue($c->quoteIdentifier('group_id'),0);
                    // import article
                    foreach($colmap as $j => $t) {
                        $val = $row[$t];
                        if(!in_array($j,array('active'))) {
                            $val = $c->quote($val);
                        } else {
                            $val = ($row['active']>3) ? 1 : 0;
                        }
                        $qb->setValue($c->quoteIdentifier($j),$val);
                    }
                    $qb->execute();
                    $articleID = \CAT\Addon\cmsbridge::getLastInsertId();
                    // map keywords to tags
                    $keywords = explode(', ',trim($row['keywords']));
                    self::importTags($targetID, $articleID, $keywords);
                    // copy picture
                    if(
                           !empty($row['picture'])
                        && file_exists(CMSBRIDGE_CMS_PATH.$settings['picture_dir'].'/'.$row['picture'])
                    ) {
                        // find free file name
                        $filename = \CAT\Addon\Journal::findFreeFilename(JOURNAL_IMGDIR.'/'.$targetID, $row['picture']);
                        // copy image and thumb
                        copy(
                            CMSBRIDGE_CMS_PATH.$settings['picture_dir'].'/'.$row['picture'],
                            JOURNAL_IMGDIR.'/'.$targetID.'/'.$filename
                        );
                        copy(
                            CMSBRIDGE_CMS_PATH.$settings['picture_dir'].'/thumbs/'.$row['picture'],
                            JOURNAL_IMGDIR.'/'.$targetID.'/thumbs/'.$filename
                        );
                        \CAT\Addon\cmsbridge::db()->query(sprintf(
                            'INSERT IGNORE INTO `%s%s` ' .
                            '(`section_id`,`picname`) VALUES ' .
                            '(%d, "%s")',
                            \CAT\Addon\cmsbridge::dbprefix(), 'mod_journal_img',
                            $targetID, $filename
                        ));
                        $picID = \CAT\Addon\cmsbridge::getLastInsertId();
                        if(!empty($picID)) {
                            \CAT\Addon\cmsbridge::db()->query(sprintf(
                                'INSERT IGNORE INTO `%s%s` ' .
                                '(`article_id`,`pic_id`,`preview`) VALUES ' .
                                '(%d,%d,"Y")',
                                \CAT\Addon\cmsbridge::dbprefix(), 'mod_journal_articles_img',
                                $articleID, $picID
                            ));
                        }
                    }
                    // create new access file
                    if(!CMSBRIDGE_CMS_BC2) {
                        if(!is_dir($dir)) {
                            @make_dir($dir,0770);
                        }
                        if (is_writable($dir)) {
                            \CAT\Addon\Journal::createAccessFile(
                                intval($articleID),
                                $dir.$row['link'].PAGE_EXTENSION,
                                time(),
                                $pageID,
                                $targetID
                            );
                        }
                    }
                }
            }
        }   // end function importTopics()
        
        
    }
}