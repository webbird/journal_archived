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

if(!class_exists('journalImport',false))
{
    final class journalImport
    {
        public static function import(int $sourceID, int $targetID)
        {
            $qb = \CAT\Addon\cmsbridge::conn()->createQueryBuilder();
            $c  = $qb->getConnection();

            self::importSettings($sourceID, $targetID);
            $old_group_to_new_id = self::importGroups($sourceID, $targetID);
            self::importTags($sourceID, $targetID);

            // import articles
            $articles = \CAT\Addon\Journal::getArticles($sourceID,true,'',false);
            if(is_array($articles) && count($articles)>0) {
                foreach($articles as $a) {
                    $qb->resetQueryParts()
                       ->insert(sprintf('%s%s',\CAT\Addon\cmsbridge::dbprefix(), 'mod_journal_articles'));
                    foreach($a as $key => $val) {
                        switch($key) {
                            case 'article_id':
                                $val = 'NULL';
                                break;
                            case 'section_id':
                                $val = $targetID;
                                break;
                            case 'group_id':
                                if(count($old_group_to_new_id)>0 && isset($old_group_to_new_id[$val])) {
                                    $val = $old_group_to_new_id[$val];
                                } else {
                                    $val = 0;
                                }
                                break;
                            case 'views':
                                $val = 0;
                                break;
                            case 'tags':
                            case 'next':
                            case 'prev':
                                $val = 'skip';
                                break;
                        }
                        if($val != 'skip') {
                            $qb->setValue($c->quoteIdentifier($key), $c->quote($val));
                        }
                    }
                    $qb->setValue($c->quoteIdentifier('copied_from'), $c->quote($sourceID));
                    $qb->execute();
                    $newID = \CAT\Addon\cmsbridge::getLastInsertId();

                    if(!CMSBRIDGE_CMS_BC2) {
                        $pageID = \CAT\Addon\cmsbridge::getPageForSection($targetID);
                        $pg = \CAT\Addon\cmsbridge::getPage($pageID);
                        $dir = WB_PATH.PAGES_DIRECTORY.$pg['link'].'/';
                        if(!is_dir($dir)) {
                            @make_dir($dir,0770);
                        }
                        if (!is_writable($dir)) {
                            self::$errors[] = self::t('Cannot write access file!');
                            return false;
                        }
                        \CAT\Addon\Journal::createAccessFile(
                            intval($newID),
                            $dir.$a['link'].PAGE_EXTENSION,
                            time(),
                            $pageID,
                            $targetID
                        );
                    }

                    // get images
                    $images = \CAT\Addon\Journal::getImages($a['article_id']);
                    if(is_array($images) && count($images)>0) {
                        foreach($images as $i) {
                            \CAT\Addon\cmsbridge::db()->query(sprintf(
                                'INSERT IGNORE INTO `%s%s` '.
                                '(`article_id`, `pic_id`, `position`, `preview`) VALUES '.
                                '(%d          , %d      , %d        , "%s" )',
                                \CAT\Addon\cmsbridge::dbprefix(),
                                'mod_journal_articles_img',
                                intval($newID),
                                intval($i['id']),
                                intval($i['position']),
                                $i['preview']
                            ));
                        }
                    }
                }
            }
        }

        /**
         *
         * @access protected
         * @return
         **/
        protected static function importGroups(int $sourceID, int $targetID)
        {
            $old_group_to_new_id = array();
            if(isset($_REQUEST['include_groups'])) {
                $groups = \CAT\Addon\Journal::getGroups($sourceID);
                $settings = \CAT\Addon\Journal::getSettingsForSection($targetID);
                if(is_array($groups) && count($groups)>0) {
                    foreach($groups as $g) {
                        \CAT\Addon\cmsbridge::db()->query(sprintf(
                            'INSERT IGNORE INTO `%s%s` '.
                            '(`section_id`, `active`, `position`, `title`) VALUES '.
                            '( %d         , %d      , %d        , "%s"   )',
                            \CAT\Addon\cmsbridge::dbprefix(),
                            'mod_journal_groups',
                            $targetID,
                            intval($g['active']),
                            intval($g['position']),
                            $g['title']
                        ));
                        $old_group_to_new_id[$g['group_id']] = \CAT\Addon\cmsbridge::getLastInsertId();
                        if(isset($g['image']) && !empty($g['image'])) {
                            $image = pathinfo($g['image'],PATHINFO_BASENAME);
                            $parentdir = pathinfo(pathinfo($g['image'],PATHINFO_DIRNAME),PATHINFO_BASENAME);
                            $targetname = str_replace($g['group_id'], $old_group_to_new_id[$g['group_id']], $image);
                            copy(CMSBRIDGE_MEDIA_FULLDIR.'/'.$parentdir.'/'.$image, CMSBRIDGE_MEDIA_FULLDIR.'/'.$settings['image_subdir'].'/'.$targetname);
                        }
                    }
                }
            }
            return $old_group_to_new_id;
        }   // end function importGroups()
        

        /**
         *
         * @access protected
         * @return
         **/
        protected static function importSettings(int $sourceID, int $targetID)
        {
            $qb = \CAT\Addon\cmsbridge::conn()->createQueryBuilder();
            $c  = $qb->getConnection();

            if(isset($_REQUEST['include_settings'])) {
                $settings = \CAT\Addon\Journal::getSettingsForSection($sourceID);
                $qb->update(sprintf('%s%s',\CAT\Addon\cmsbridge::dbprefix(), 'settings'))
                   ->where('`section_id`='.$targetID);
                foreach($settings as $key => $val) {
                    if(!in_array($key,array('view_order','articles_per_page'))) {
                        $val = $c->quote($val);
                    }
                    $qb->set($c->quoteIdentifier($key),$val);
                }
                $qb->execute();
            }
        }   // end function importSettings()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function importTags(int $sourceID, int $targetID)
        {
            if(isset($_REQUEST['include_tags'])) {
                $tags = \CAT\Addon\Journal::getTags($sourceID,true);
                if(is_array($tags) && count($tags)>0) {
                    foreach($tags as $t) {
                        \CAT\Addon\cmsbridge::db()->query(sprintf(
                            'INSERT IGNORE INTO `%s%s` '.
                            '(`section_id`, `tag_id`) VALUES '.
                            '( %d         , %d  )',
                            \CAT\Addon\cmsbridge::dbprefix(),
                            'mod_journal_tags_sections',
                            $targetID,
                            intval($t['tag_id'])
                        ));
                    }
                }
            }
        }   // end function importTags()
        
    }
}