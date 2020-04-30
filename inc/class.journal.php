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

namespace CAT\Addon;

#require_once __DIR__.'/vendor/autoload.php';

spl_autoload_register(function($class)
{
    $file = str_replace('\\', '/', $class);
    $parts = explode('/',$file);
    if(isset($parts[2]) && in_array($parts[2],array('journal','cmsbridge')))
    {
        $file = str_ireplace('CAT/Addon/','',$file);
        if(file_exists(__DIR__.'/'.$file.'.php')) {
            require __DIR__.'/'.$file.'.php';
        } elseif(file_exists(__DIR__.'/class.'.$file.'.php')) {
            require __DIR__.'/class.'.$file.'.php';
        }
    }
    // next in stack
});

if(!class_exists('journal',false))
{
    final class journal
    {
        /**
         * module info
         **/
        protected static $type        = 'page';
        protected static $directory   = 'journal';
        protected static $name        = 'journal';
        protected static $version     = '0.1';
        protected static $description = "";
        protected static $author      = "BlackBird Webprogrammierung";
        protected static $guid        = "";
        protected static $license     = "GNU General Public License";

        /**
         * @var some defaults
         **/
        protected static $defaults    = array(
            'tag_color'        => '#b0c4de',
            'text_color'       => '#000',
            'hover_color'      => '#9cc',
            'text_hover_color' => '#000',
        );
        /**
         * @var settings cache
         **/
        protected static $settings    = null;
        /**
         * @var current section
         **/
        protected static $sectionID   = null;
        /**
         * @var current page
         **/
        protected static $pageID      = null;
        /**
         * @var properties of current page
         **/
        protected static $page        = null;
        /**
         * @var globals for all templates
         **/
        protected static $tpldata     = array();
        /**
         * @var global error messages
         **/
        protected static $errors      = array();
        /**
         * @var global info messages
         **/
        protected static $info        = array();
        /**
         * @var form fields to highlight
         **/
        protected static $highlighted = array();
        /**
         * @var table names
         **/
        protected static $tables      = array();

        public static function getInfo(string $value=null) : array
        {
            return cmsbridge::getInfo($value);
        }   // end function getInfo()

        public static function initialize(array $section) {
            cmsbridge::initialize($section);
            if(CMSBRIDGE_CMS_BC2) {
                $lang_path = \CAT\Helper\Directory::sanitizePath(implode('/',array(CAT_ENGINE_PATH,CAT_MODULES_FOLDER,$section['module'],CAT_LANGUAGES_FOLDER)));
                if (is_dir($lang_path)) {
                    \CAT\Base::lang()->addPath($lang_path,'JOURNAL_LANG');
                }
                define('PAGE_SPACER','_');
                define('PAGE_EXTENSION','');
            }
            if(CMSBRIDGE_CMS_WBCE) {
                cmsbridge::setLangVar('JOURNAL_LANG');
            }
            define('JOURNAL_IMAGE_SUBDIR','journal');
            define('JOURNAL_DIR'     ,pathinfo(__DIR__,PATHINFO_DIRNAME));
            define('JOURNAL_MODDIR'  ,pathinfo(pathinfo(__DIR__,PATHINFO_DIRNAME),PATHINFO_BASENAME));
            define('JOURNAL_IMGDIR'  ,CMSBRIDGE_CMS_PATH.'/'.CMSBRIDGE_MEDIA.'/'.JOURNAL_IMAGE_SUBDIR);
            define('JOURNAL_IMGURL'  ,CMSBRIDGE_CMS_URL.'/'.CMSBRIDGE_MEDIA.'/'.JOURNAL_IMAGE_SUBDIR);
            define('JOURNAL_NOPIC'   ,CMSBRIDGE_CMS_URL."/modules/".JOURNAL_MODDIR."/images/nopic.png");

            define('JOURNAL_TITLE_SPACER', ' # ');

            self::$sectionID = intval($section['section_id']);
            self::$pageID    = cmsbridge::pagefor(self::$sectionID);
            self::$page      = cmsbridge::getPage(self::$pageID);
            if(!CMSBRIDGE_CMS_BC2) {
                self::$page['route'] = self::$page['link'];
            }
            
            self::$tpldata = array(
                'curr_tab'      => 'p',
                'edit_url'      => cmsbridge::getRoute('/pages/edit/{id}').(CMSBRIDGE_CMS_BC2 ? '?' : '&amp;'),
                'form_edit_url' => cmsbridge::getRoute('/pages/edit/{id}'),
            );

            self::$tables = array(
                'settings' => 'mod_'.JOURNAL_MODDIR.'_settings',
                'articles' => 'mod_'.JOURNAL_MODDIR.'_articles',
                'groups'   => 'mod_'.JOURNAL_MODDIR.'_groups',
                'images'   => 'mod_'.JOURNAL_MODDIR.'_img',
                'tags'     => 'mod_'.JOURNAL_MODDIR.'_tags',
                'articles_img'  => 'mod_'.JOURNAL_MODDIR.'_articles_img',
                'tags_sections' => 'mod_'.JOURNAL_MODDIR.'_tags_sections',
                'tags_articles' => 'mod_'.JOURNAL_MODDIR.'_tags_articles',
            );
        }

        /**
         * called by modify.php in BC1 and WBCE and by \CAT\Backend in BC2
         *
         * @access public
         * @param  array   $section - section data from database
         * @return string
         **/
        public static function modify(array $section) : string
        {
            $info = $error   = null;

            // tab to activate
            $curr_tab = 'p';
            if (isset($_REQUEST['tab']) && in_array($_REQUEST['tab'], array('p','g','s','o','r'))) {
                $curr_tab = $_REQUEST['tab'];
            }

            // cleanup database (orphaned)
            try {
                cmsbridge::db()->query(sprintf(
                    "DELETE FROM `%s%s` WHERE `section_id`=%d and `title`=''",
                    cmsbridge::dbprefix(),self::$tables['articles'],self::$sectionID
                ));
                cmsbridge::db()->query(sprintf(
                    "DELETE FROM `%s%s` WHERE `section_id`=%d and `title`=''",
                    cmsbridge::dbprefix(),self::$tables['groups'],self::$sectionID
                ));
            } catch ( \PDOException $e ) {
            }

            // switch action
            switch($curr_tab) {
                case 'g':
                    if(isset($_REQUEST['add_group']) && !empty($_REQUEST['title'])) {
                        self::addGroup();
                    }
                    if(isset($_REQUEST['del_group'])) {
                        if(!self::delGroup()) {
                            $error = cmsbridge::t('Unable to delete the group!');
                        } else {
                            $info = cmsbridge::t('Group successfully deleted');
                        }
                    }
                    if(isset($_REQUEST['group_id'])) {
                        $ret = self::editGroup(intval($_REQUEST['group_id']));
                        if($ret!="1") {
                            return $ret;
                        }
                    }
                    break;
                case 'o':
                    if(isset($_REQUEST['mode']) && in_array($_REQUEST['mode'],array('default','advanced'))) {
                        cmsbridge::db()->query(sprintf(
                            'UPDATE `%s%s` SET `mode`="%s"',
                            cmsbridge::dbprefix(), self::$tables['settings'],
                            $_REQUEST['mode']
                        ));
                    }
                    if(isset($_REQUEST['save_settings']) || isset($_REQUEST['preset_name'])) {
                        self::saveSettings();
                    }
                    break;
                case 'p':
                    if(isset($_REQUEST['mod_journal_add_article'])) {
                        return self::addArticle();
                    }
                    if(isset($_REQUEST['article_id'])) {
                        $ret = self::editArticle(intval($_REQUEST['article_id']));
                        if($ret!="1") {
                            return $ret;
                        }
                    }
                    break;
                case 's':
                    if(isset($_REQUEST['new_tag'])) {
                        self::addTag();
                    }
                    if(isset($_REQUEST['tag_id']) && !isset($_REQUEST['cancel'])) {
                        return self::editTag();
                    }
                    break;
            }

            $articles = self::getArticles(true, '');
            $groups   = self::getGroups();
            $article_data = array(
                'curr_tab'     => $curr_tab,
                'is_admin'     => false,
                'articles'     => $articles,
                'num_articles' => count($articles),
                'groups'       => $groups,
                'num_groups'   => 0,
                'tags'         => self::getTags(self::$sectionID),
                'lang_map'     => array(
                    0 => cmsbridge::t('Custom'),
                    1 => cmsbridge::t('Publishing start date').', '.cmsbridge::t('descending'),
                    2 => cmsbridge::t('Publishing end date').', '.cmsbridge::t('descending'),
                    3 => cmsbridge::t('Submitted').', '.cmsbridge::t('descending'),
                    4 => cmsbridge::t('ID').', '.cmsbridge::t('descending')
                ),
                'sizes'        => self::getSizePresets(),
                'views'        => self::getViews(),
                'FTAN'         => cmsbridge::getFTAN(),
                'info'         => $info,
                'error'        => $error,
                // WBCE only --->
                'importable_sections' => 0,
                // <---
            );

            if(CMSBRIDGE_CMS_WBCE) {
                global $admin;
                $article_data['is_admin'] = in_array(1, $admin->get_groups_id());
            }
            if(CMSBRIDGE_CMS_BC2) {
// !!!!! TODO: permissions !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
                $article_data['is_admin'] = \CAT\Base::user()->isAuthenticated();
            }

            return self::printPage(
                __DIR__.'/../templates/default/modify.phtml',
                $article_data
            );
        }

        /**
         *
         * @access public
         * @return
         **/
        public static function view(array $section)
        {
            self::$sectionID = intval($section['section_id']);
            self::$pageID    = cmsbridge::pagefor(self::$sectionID);

            if(CMSBRIDGE_CMS_BC2) { // resolve route
                $this_route = \CAT\Base::router()->getRoute();
                $page_name  = \CAT\Helper\Page::properties(self::$pageID, 'menu_title');
                $article       = str_ireplace($page_name.'/', '', $this_route);
                if(strlen($article) && $article != $page_name) {
                    $articleID = self::getArticleByLink(urldecode($article));
                    echo self::readArticle($articleID);
                    return;
                }
            }

            // list
            $articles = self::getArticles(false, self::getQueryExtra(), true);
            echo self::renderList($articles);
        }   // end function view()
        
        /**
         *
         * @access public
         * @return
         **/
        public static function admin()
        {
            return cmsbridge::admin();
        }   // end function admin()

        /**
         *
         * @access public
         * @return
         **/
        public static function printHeaders(array $data)
        {
            $content = "";
            if(CMSBRIDGE_CMS_WBCE) {
                $content = "<!--(MOVE) CSS HEAD TOP- -->\n"
                         . "<!-- JOURNAL module design file -->\n"
                         . '<link rel="stylesheet" href="'.CMSBRIDGE_CMS_URL.'/modules/'.JOURNAL_MODDIR.'/views/'.$data['view'].'/frontend.css" />'."\n"
                         . '<link rel="stylesheet" href="'.CMSBRIDGE_CMS_URL.'/modules/'.JOURNAL_MODDIR.'/js/galleries/'.self::$settings['gallery'].'/frontend.css" />'."\n"
                         . "<!--(END)-->\n";
                if(isset($data['article']['page_title'])) {
                    $content .= "<!--(REPLACE) META DESC -->\n"
                             .  '<meta name="description" content="'.$data['article']['page_title'].'"/>'."\n"
                             .  "<!--(END)-->\n";
                }
            }
            if(CMSBRIDGE_CMS_BC2) {
                // view
                \CAT\Helper\Assets::addCSS("/modules/".JOURNAL_MODDIR."/views/".$data['view']."/frontend.css");
                // gallery
                \CAT\Helper\Assets::addCSS("/modules/".JOURNAL_MODDIR."/js/galleries/".self::$settings['gallery']."/frontend.css");
                // page title
                if(isset($data['article']['page_title'])) {
                    \CAT\Helper\Page::setTitle($data['article']['page_title']);
                }
            }
            return $content;
        }   // end function printHeaders()
        
        
        /**
         *
         * @access public
         * @return
         **/
        public static function t(string $msg)
        {
            return cmsbridge::t($msg);
        }   // end function t()

        /**
         *
         * @access public
         * @return
         **/
        public static function wysiwyg($id,$content,$width,$height)
        {
            return cmsbridge::wysiwyg($id,$content,$width,$height);
        }   // end function t()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function addGroup()
        {
            $title  = cmsbridge::escapeString(strip_tags($_REQUEST['title']));
        	$active = ( isset($_REQUEST['active']) && $_REQUEST['active']=='1' )
                    ? 1 : 0;
            cmsbridge::db()->query(sprintf(
                "INSERT INTO `%s%s` (`section_id`,`active`,`position`,`title`) " .
                "VALUES (%d,%d,0,'%s')",
                cmsbridge::dbprefix(), self::$tables['groups'],
                self::$sectionID, $active, $title
            ));
            return !(cmsbridge::db()->errorCode()==0);
        }   // end function addGroup()
        
        /**
         *
         * @access protected
         * @return
         **/
        protected static function addArticle()
        {
            $id  = null;
            // Insert new row into database
            $sql = "INSERT INTO `%s%s` " .
                   "(`section_id`,`position`,`link`,`content_short`,`content_long`,`content_block2`,`active`) ".
                   "VALUES ('%s','0','','','','','1')";
            cmsbridge::db()->query(sprintf($sql, cmsbridge::dbprefix(), self::$tables['articles'], self::$sectionID));
            if(cmsbridge::db()->errorCode()==0) {
                $stmt = cmsbridge::db()->query('SELECT LAST_INSERT_ID() AS `id`');
                $res  = $stmt->fetch();
                $id   = $res['id'];
            }
            return self::editArticle(self::$sectionID, intval($id));
        }   // end function addArticle()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function addTag()
        {
            $title  = cmsbridge::escapeString(strip_tags($_REQUEST['new_tag']));
        	$global = ( isset($_REQUEST['global_tag']) && $_REQUEST['global_tag']=='1' )
                    ? 1 : 0;

            // some defaults
            foreach(array('tag_color','text_color','hover_color','text_hover_color') as $key) {
                $d[$key] = (isset($_REQUEST[$key]) ? cmsbridge::escapeString(strip_tags($_REQUEST[$key])) : self::$defaults[$key]);
            }

            cmsbridge::db()->query(sprintf(
                "INSERT INTO `%s%s` (`tag`,`tag_color`,`text_color`,`hover_color`,`text_hover_color`) " .
                "VALUES ('%s','%s','%s','%s','%s')",
                cmsbridge::dbprefix(), self::$tables['tags'],
                $title, $d['tag_color'], $d['text_color'], $d['hover_color'], $d['text_hover_color']
            ));

            if(cmsbridge::db()->errorCode()==0) {
                $stmt   = cmsbridge::db()->query('SELECT LAST_INSERT_ID() AS `id`');
                $res    = $stmt->fetch();
                $tag_id = $res['id'];
            }

            if(!empty($tag_id)) {
                $tag_section_id = ($global==1 ? 0 : self::$sectionID);
                cmsbridge::db()->query(sprintf(
                    "INSERT INTO `%s%s` (`section_id`,`tag_id`) VALUES (%d, %d);",
                    cmsbridge::dbprefix(), self::$tables['tags_sections'],
                    $tag_section_id, $tag_id
                ));
            }

            return !(cmsbridge::db()->errorCode()==0);
        }   // end function addTag()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function delGroup() : bool
        {
            $gid = intval($_REQUEST['del_group']);
            if(self::groupExists($gid)) {
                // move articles to "no group"
                cmsbridge::db()->query(sprintf(
                    "UPDATE `%s%s` SET `group_id`=0 WHERE `group_id`=%d",
                    cmsbridge::dbprefix(), self::$tables['articles'], $gid
                ));
                // delete group
                cmsbridge::db()->query(sprintf(
                    "DELETE FROM `%s%s` WHERE `group_id`=%d",
                    cmsbridge::dbprefix(), self::$tables['groups'], $gid
                ));
                return !(cmsbridge::db()->errorCode()==0);
            }
            return false;
        }   // end function delGroup()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function editArticle(int $articleID) : string
        {
            // just activate/deactivate?
            if(isset($_REQUEST['active']) && !isset($_REQUEST['save'])) {
                $newval = (intval($_REQUEST['active'])==1) ? 1 : 0;
                cmsbridge::db()->query(sprintf(
                    'UPDATE `%s%s` SET `active`=%d WHERE `article_id`=%d',
                    cmsbridge::dbprefix(), self::$tables['articles'], $newval, $articleID
                ));
                return "1";
            }

            // move up / down?
            if(isset($_REQUEST['move']) && in_array($_REQUEST['move'],array('up','down'))) {
                $result = self::moveUpDown($articleID, $_REQUEST['move']);
                return "1";
            }

            // save?
            if(isset($_REQUEST['save'])) {
                self::saveArticle();
            }

            $article_data = self::getArticle($articleID);
            $article_data['linkbase'] = '';
            # in BC2, the linkbase is always the current page
            if(!CMSBRIDGE_CMS_BC2) {
                $link  = $article_data['link'];
                $parts = explode('/', $link);
                $link  = array_pop($parts);
                $article_data['linkbase'] = implode('/', $parts);
            } else {
                $article_data['linkbase'] = \CAT\Helper\Page::getLink(self::$pageID);
                \CAT\Helper\Assets::addCSS(CAT_MODULES_FOLDER.'/'.JOURNAL_MODDIR.'/uploader/styles.css');
            }

            $date_format = CMSBRIDGE_CMS_BC2
                ? \CAT\Base::getSetting('date_format')
                : DATE_FORMAT;

            // calendar
            switch($date_format) {
            	case 'd.m.Y':
            	case 'd M Y':
            	case 'l, jS F, Y':
            	case 'jS F, Y':
            	case 'D M d, Y':
            	case 'd-m-Y':
            	case 'd/m/Y':
            		$article_data['jscal_format'] = 'd.m.Y'; // dd.mm.yyyy hh:mm
            		$article_data['jscal_ifformat'] = '%d.%m.%Y';
            		break;
            	case 'm/d/Y':
            	case 'm-d-Y':
            	case 'M d Y':
            	case 'm.d.Y':
            		$article_data['jscal_format'] = 'm/d/Y'; // mm/dd/yyyy hh:mm
            		$article_data['jscal_ifformat'] = '%m/%d/%Y';
            		break;
            	default:
            		$article_data['jscal_format'] = 'Y-m-d'; // yyyy-mm-dd hh:mm
            		$article_data['jscal_ifformat'] = '%Y-%m-%d';
            		break;
            }

            $article_data['images']   = self::getImages($articleID,false);
            $article_data['tags']     = self::getTags(self::$sectionID,true);
            $article_data['assigned'] = self::getAssignedTags($articleID);

            return self::printPage(
                __DIR__.'/../templates/default/modify_article.phtml',
                $article_data
            );
        }   // end function editArticle()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function editGroup()
        {
            $gid = intval($_REQUEST['group_id']);
            if(self::groupExists($gid)) {
                // just activate/deactivate?
                if(isset($_REQUEST['active']) && !isset($_REQUEST['save'])) {
                    $newval = (intval($_REQUEST['active'])==1) ? 1 : 0;
                    cmsbridge::db()->query(sprintf(
                        'UPDATE `%s%s` SET `active`=%d WHERE `group_id`=%d',
                        cmsbridge::dbprefix(), self::$tables['groups'], $newval, $gid
                    ));
                    return "1";
                }
            }
        }   // end function editGroup()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function editTag()
        {
            $tagID = intval($_REQUEST['tag_id']);
            $tag   = self::getTag($tagID);

            if(!is_array($tag) || !isset($tag['tag_id']) || empty($tag['tag_id'])) {
                self::$errors[] = self::t('No such tag');
                return false;
            }

            if(isset($_REQUEST['save_tag'])) {
                // tag name is mandatory
                $title = cmsbridge::escapeString($_REQUEST['tag']);
                if(empty($title)) {
                    self::$errors[] = self::t('Title is mandatory!');
                    return false;
                }

                $tag_color = cmsbridge::escapeString($_REQUEST['tag_color']);
                $hover_color = cmsbridge::escapeString($_REQUEST['hover_color']);

                foreach(array('tag_color','text_color','hover_color','text_hover_color') as $key) {
                    $d[$key] = (isset($_REQUEST[$key]) ? cmsbridge::escapeString(strip_tags($_REQUEST[$key])) : '');
                }
                cmsbridge::db()->query(sprintf(
                    'UPDATE `%s%s` SET `tag`="%s", '.
                    '`tag_color`="%s", `hover_color`="%s", '.
                    '`text_color`="%s", `text_hover_color`="%s" '.
                    'WHERE `tag_id`=%d',
                    cmsbridge::dbprefix(), self::$tables['tags'].
                    $title, $d['tag_color'], $d['hover_color'],
                    $d['text_color'], $d['text_hover_color'], $tagID
                ));
                $tag   = self::getTag($tagID);
            }

            return self::printPage(
                __DIR__.'/../templates/default/modify_tag.phtml',
                array('tag'=>$tag)
            );
        }   // end function editTag()
        
        /**
         *
         * @access protected
         * @return
         **/
        protected static function escapeTags(string $tags) : array
        {
            if(!strlen($tags)) {
                return array();
            }
            $tags = explode(",", $tags);
            foreach($tags as $i => $tag) {
                $tags[$i] = cmsbridge::escapeString($tag);
            }
            return $tags;
        }   // end function escapeTags()
        

        /**
         *
         * @access protected
         * @return
         **/
        protected static function getAllowedSuffixes()
        {
// !!!!! TODO: aus den CMS-Einstellungen auslesen !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            return array('jpg','jpeg','gif','png');
        }   // end function getAllowedSuffixes()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function getAssignedTags(int $articleID)
        {
            $tags = array();

            $query_tags = cmsbridge::db()->query(sprintf(
                "SELECT  t1.* " .
                "FROM `%s%s` AS t1 " .
                "JOIN `%s%s` AS t2 " .
                "ON t1.`tag_id`=t2.`tag_id` ".
                "JOIN `%s%s` AS t3 ".
                "ON t2.`article_id`=t3.`article_id` ".
                "WHERE t2.`article_id`=%d",
                cmsbridge::dbprefix(), self::$tables['tags'],
                cmsbridge::dbprefix(), self::$tables['tags_articles'],
                cmsbridge::dbprefix(), self::$tables['articles'], $articleID
            ));

            if (!empty($query_tags) && $query_tags->rowCount() > 0) {
                while($t = $query_tags->fetch()) {
                    $tags[$t['tag_id']] = $t;
                }
            }

            return $tags;
        }   // end function getAssignedTags()
        
        /**
         *
         * @access protected
         * @return
         **/
        protected static function getBytes(string $val) : int
        {
            $val  = trim($val);
            $last = strtolower($val[strlen($val)-1]);
            $val  = intval($val);
            switch ($last) {
                case 'g':
                    $val *= 1024;
                    // no break
                case 'm':
                    $val *= 1024;
                    // no break
                case 'k':
                    $val *= 1024;
            }

            return $val;
        }   // end function getBytes()
        
        /**
         *
         * @access protected
         * @return
         **/
        protected static function getGroup(int $groupID)
        {
            $query  = cmsbridge::db()->query(sprintf(
                "SELECT * FROM `%s%s` " .
                "WHERE `group_id`=%d",
                cmsbridge::dbprefix(), self::$tables['groups'], $groupID
            ));
            if ($query->rowCount() > 0) {
                return $query->fetch();
            }
        }   // end function getGroup()
        
        /**
         * get groups for section with ID $section_id
         *
         * @param   int   $section_id
         * @return
         **/
        protected static function getGroups() : array
        {
            $groups = array();
            $query  = cmsbridge::db()->query(sprintf(
                "SELECT * FROM `%s%s` " .
                "WHERE `section_id`=%d ORDER BY `position` ASC",
                cmsbridge::dbprefix(), self::$tables['groups'], self::$sectionID
            ));
            if ($query->rowCount() > 0) {
                $allowed_suffixes = self::getAllowedSuffixes();
                // Loop through groups
                while ($group = $query->fetch()) {
                    $group['id_key'] = cmsbridge::admin()->getIDKEY($group['group_id']);
                    $group['image']  = '';
                    foreach($allowed_suffixes as $suffix) {
                        if (file_exists(JOURNAL_IMGDIR.'/image'.$group['group_id'].'.'.$suffix)) {
                            $group['image'] = JOURNAL_IMGURL.'/image'.$group['group_id'].'.'.$suffix;
                            break;
                        }
                    }
                    $groups[] = $group;
                }
            }
            return $groups;
        }   // end function getGroups()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function getImages(int $articleID)
        {
            $settings = self::getSettings();
            $stmt = cmsbridge::db()->query(sprintf(
                "SELECT * FROM `%s%s` t1 " .
                "JOIN `%s%s` t2 ".
                "ON t1.`id`=t2.`pic_id` ".
                "WHERE `t2`.`article_id`=%d " .
                "ORDER BY `position`,`id` ASC",
                cmsbridge::dbprefix(), self::$tables['images'],
                cmsbridge::dbprefix(), self::$tables['articles_img'],
                intval($articleID)
            ));
            return $stmt->fetchAll();
        }   // end function getImages()

        /**
         *
         * @access
         * @return
         **/
        protected static function getOrder()
        {
            $settings  = self::getSettings(self::$sectionID);
            $order_by  = 'position'; // default
            $direction = 'ASC';
            if($settings['view_order']!=0) {
            switch($settings['view_order']) {
                case 1:
                    $order_by = "published_when";
                    $direction = 'DESC';
                    break;
                case 2:
                    $order_by = "published_until";
                    $direction = 'DESC';
                    break;
                case 3:
                    $order_by = "posted_when";
                    $direction = 'DESC';
                    break;
                case 4:
                    $order_by = "article_id";
                    $direction = 'DESC';
                    break;
            }
            }
            return array($order_by,$direction);
        }   // end function getOrder()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function getPlaceholders()
        {
            // all known placeholders
            $vars = array(
                'BACK',                         // back to list link
                'CONTENT',                      // content_short + content_long
                'CONTENT_BLOCK2',               // optional block 2
                'CONTENT_LONG',                 // long content
                'CONTENT_SHORT',                // short content (teaser)
                'CREATED_DATE',                 // article added
                'CREATED_TIME',                 // article added time
                'DISPLAY_GROUP',                // wether to show the group name
                'DISPLAY_IMAGE',                // wether to show the preview image
                'DISPLAY_NAME',                 // user's (who posted) display name
                'DISPLAY_PREVIOUS_NEXT_LINKS',  // wether to show prev/next
                'EMAIL',                        // user's (who posted) email address
                'GROUP_ID',                     // ID of the group the article is linked to
                'GROUP_IMAGE',                  // image of the group
                'GROUP_IMAGE_URL',              // image url
                'GROUP_TITLE',                  // group title
                'IMAGE',                        // preview image
        		'IMAGE_URL',					// URL of preview image without <img src>
                'IMAGES',                       // gallery images
                'LINK',                         // "read more" link
                'MODI_DATE',                    // article modification date
                'MODI_TIME',                    // article modification time
                'NEXT_LINK',                    // next link
                'NEXT_PAGE_LINK',               // next page link
                'OF',                           // text "of" ("von")
                'OUT_OF',                       // text "out of" ("von")
                'PAGE_TITLE',                   // page title
                'POST_ID',                      // ID of the article
                'POST_OR_GROUP_IMAGE',          // use group image if there's no preview image
                'POST_TAGS',
                'PREVIOUS_LINK',                // prev link
                'PREVIOUS_PAGE_LINK',           // prev page link
                'PUBLISHED_DATE',               // published date
                'PUBLISHED_TIME',               // published time
                'SHORT',                        // alias for CONTENT_SHORT
                'SHOW_READ_MORE',               // wether to show "read more" link
                'TAGS',                         // tags
                'TAG_LINK',
                'TAG',
                'TAGCOLOR',                     // background color for tag
                'TAGHOVERCOLOR',                // mouseover background color for tag
                'TEXTCOLOR',
                'TEXTHOVERCOLOR',
                'TEXT_AT',                      // text for "at" ("um")
                'TEXT_BACK',                    // text for "back" ("zurueck")
                'TEXT_LAST_CHANGED',            // text for "last changed" ("zuletzt geaendert")
                'TEXT_NEXT_POST',               // text for "next article" ("naechster Beitrag")
                'TEXT_O_CLOCK',                 // text for "o'clock" ("Uhr")
                'TEXT_ON',                      // text for "on" ("am")
                'TEXT_POSTED_BY',               // text for "posted by" ("verfasst von")
                'TEXT_PREV_POST',               // text for "previous article" ("voriger Beitrag")
                'TEXT_READ_MORE',               // text for "read more" ("Weiterlesen")
                'TITLE',                        // article title (heading)
                'USER_ID',                      // user's (who posted) ID
                'USERNAME',                     // user's (who posted) username
            );
            $default_replacements = array(
                'TEXT_AT'           => self::t('at'),
                'TEXT_BACK'         => self::t('Back'),
                'TEXT_LAST_CHANGED' => self::t('Last modified'),
                'TEXT_NEXT_POST'    => self::t('Next article'),
                'TEXT_O_CLOCK'      => self::t("o'clock"),
                'TEXT_ON'           => self::t('on'),
                'TEXT_POSTED_BY'    => self::t('Posted by'),
                'TEXT_PREV_POST'    => self::t('Previous article'),
                'TEXT_READ_MORE'    => self::t('Read more'),
            );
            return array($vars,$default_replacements);
        }   // end function getPlaceholders()
        
        /**
         *
         * @access protected
         * @return
         **/
        protected static function getArticle(int $articleID)
        {
            list($order_by,$direction) = self::getOrder(self::$sectionID);
            $prev_dir = ($direction=='DESC'?'ASC':'DESC');
            $sql = sprintf(
                "SELECT `t1`.*, " .
                "  (SELECT `link` FROM `%s%s` AS `t2` WHERE `t2`.`$order_by` > `t1`.`$order_by` AND `section_id`=%d AND `active`=1 ORDER BY `$order_by` $prev_dir LIMIT 1 ) as `prev_link`, ".
                "  (SELECT `link` FROM `%s%s` AS `t3` WHERE `t3`.`$order_by` < `t1`.`$order_by` AND `section_id`=%d AND `active`=1 ORDER BY `$order_by` $direction LIMIT 1 ) as `next_link` " .
                "FROM `%s%s` AS `t1` " .
                "WHERE `article_id`=%d",
                cmsbridge::dbprefix(), self::$tables['articles'], self::$sectionID,
                cmsbridge::dbprefix(), self::$tables['articles'], self::$sectionID,
                cmsbridge::dbprefix(), self::$tables['articles'], $articleID
            );
            $stmt = cmsbridge::db()->query($sql);
            if(!empty($stmt)) {
                $article = $stmt->fetch();
                // get users
                $users = self::getUsers();
                // add "unknown" user
                $users[0] = array(
                    'username' => 'unknown',
                    'display_name' => 'unknown',
                    'email' => ''
                );
                return self::processArticle($article, $users);
            }
            return array();
        }   // end function getArticle()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function getArticleByLink(string $link) : int
        {
            $stmt = cmsbridge::db()->query(sprintf(
                "SELECT `article_id` FROM `%s%s` WHERE ".
                "`link`='%s' AND `section_id`=%d",
                cmsbridge::dbprefix(), self::$tables['articles'], $link, self::$sectionID
            ));
            if(!empty($stmt)) {
                $article = $stmt->fetch();
                return ( isset($article['article_id']) ? intval($article['article_id']) : 0);
            }
            return 0;
        }   // end function getArticleByLink()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function getArticleCount()
        {
            $query_extra = self::getQueryExtra();
            $t = time();
            $sql = sprintf(
                "SELECT count(`article_id`) AS `count` " .
                "FROM `%s%s` AS `t1` " .
                "WHERE `section_id`=%d ".
                "AND `active`=1 AND `title`!='' " .
                "AND (`published_when`  = '0' OR `published_when` <= $t) " .
                "AND (`published_until` = '0' OR `published_until` >= $t) " .
                "$query_extra ",
                cmsbridge::dbprefix(), self::$tables['articles'], self::$sectionID
            );
            $stmt = cmsbridge::db()->query($sql);
            if(!empty($stmt) && $stmt->numRows()>0) {
                $r = $stmt->fetch();
                return $r['count'];
            }
            return 0;
        }   // end function getArticleCount()

        /**
         *
         * @access
         * @return
         **/
        protected static function getArticles(bool $is_backend, string $query_extra, bool $process=true)
        {
            $articles    = array();
            $groups   = self::getGroups(self::$sectionID);
            $t        = time();
            $limit    = '';
            $filter   = '';
            $active   = '';

            list($order_by,$direction) = self::getOrder(self::$sectionID);

            self::getSettings(self::$sectionID);

            if (self::$settings['articles_per_page'] != 0) {
                if (isset($_GET['p']) and is_numeric($_GET['p']) and $_GET['p'] >= 0) {
                    $position = intval($_GET['p']);
                } else {
                    $position = 0;
                }
                if(!$is_backend) {
                    $limit = " LIMIT $position,".self::$settings['articles_per_page'];
                }
            }

            if(!$is_backend) {
                $query_extra .= " AND `active` = '1' AND `title` != ''"
                             .  " AND (`published_when`  = '0' OR `published_when`  <= $t)"
                             .  " AND (`published_until` = '0' OR `published_until` >= $t)";
                $active      =  ' AND `active`=1';
            }

            if(isset($_GET['tags']) && strlen($_GET['tags'])) {
                $filter_articles = array();
                $tags = self::escacpeTags($_GET['tags']);
                $r    = cmsbridge::db()->query(sprintf(
                    "SELECT `t2`.`article_id` FROM `%s%s` as `t1` ".
                    "JOIN `%s%s` AS `t2` ".
                    "ON `t1`.`tag_id`=`t2`.`tag_id` ".
                    "WHERE `tag` IN ('".implode("', '", $tags)."') ".
                    "GROUP BY `t2`.`article_id`",
                    cmsbridge::dbprefix(), self::$tables['tags'],
                    cmsbridge::dbprefix(), self::$tables['tags_articles']
                ));
                while ($row = $r->fetch()) {
                    $filter_articles[] = $row['article_id'];
                }
                if (count($filter_articles)>0) {
                    $filter = " AND `t1`.`article_id` IN (".implode(',', array_values($filter_articles)).") ";
                }
            }

            $prev_dir = ($direction=='DESC'?'ASC':'DESC');

            $sql = sprintf(
                "SELECT " .
                "  *, " .
                "  (SELECT COUNT(`article_id`) FROM `%s%s` AS `t2` WHERE `t2`.`article_id`=`t1`.`article_id`) as `tags`, " .
                "  (SELECT `article_id` FROM `%s%s` AS `t3` WHERE `t3`.`$order_by` > `t1`.`$order_by` AND `section_id`='".self::$sectionID."' $active ORDER BY `$order_by` $direction LIMIT 1 ) as `next`, ".
                "  (SELECT `article_id` FROM `%s%s` AS `t3` WHERE `t3`.`$order_by` < `t1`.`$order_by` AND `section_id`='".self::$sectionID."' $active ORDER BY `$order_by` $prev_dir LIMIT 1 ) as `prev` " .
                "FROM `%s%s` AS `t1` WHERE `section_id`='".self::$sectionID."' $filter ".
                "$query_extra ORDER BY `$order_by` $direction $limit",
                cmsbridge::dbprefix(), self::$tables['tags_articles'],
                cmsbridge::dbprefix(), self::$tables['articles'],
                cmsbridge::dbprefix(), self::$tables['articles'],
                cmsbridge::dbprefix(), self::$tables['articles']
            );

            $query_articles = cmsbridge::db()->query($sql);

            if(!empty($query_articles) && $query_articles->rowCount()>0) {
                // map group index to title
                $group_map = array();
                foreach($groups as $i => $g) {
                    $group_map[$g['group_id']] = ( empty($g['title']) ? self::t('none') : $g['title'] );
                }
                // get users
                $users = self::getUsers();
                // add "unknown" user
                $users[0] = array(
                    'username'     => 'unknown',
                    'display_name' => 'unknown',
                    'email'        => ''
                );
                while($article = $query_articles->fetch()) {
                    if($process === true) {
                        $articles[] = self::processArticle($article, $users);
                	} else {
                    	$articles[] = $article;
                    }
                }
            }

            return $articles;
        }   // end function getArticles()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function getPreviewImage(array $images, string $alt, $articleID)
        {
            // preview image
            if(!empty($images)) {
                foreach($images as $img) {
                    if($img['previmg']=='Y') {
                        return "<img src='".JOURNAL_IMGURL.'/'.$articleID.'/thumb/'.$img['picname']."' alt='".$alt."' />";
                    }
                }
            }
        }   // end function getPreviewImage()
        
        /**
         *
         * @access protected
         * @return
         **/
        protected static function getPrevNextLinks()
        {
            $cnt = self::getArticleCount(); // all articles in this section

/*
            $total_num = $cnt['count'];
            if ($position > 0) {
                $pl_prepend = '<a href="?p='.($position-$articles_per_page).(empty($tags_append) ? '' : '&amp;tags='.$tags_append).'">';
                if (isset($_GET['g']) and is_numeric($_GET['g'])) {
                    $pl_prepend = '<a href="?p='.($position-$articles_per_page).(empty($tags_append) ? '' : '&amp;tags='.$tags_append).'&amp;g='.$_GET['g'].'">';
                }
                $pl_append = '</a>';
                $previous_link = $pl_prepend.$TEXT['PREVIOUS'].$pl_append;
                $previous_page_link = $pl_prepend.$TEXT['PREVIOUS_PAGE'].$pl_append;
            } else {
                $previous_link = '';
                $previous_page_link = '';
            }
            if ($position + $articles_per_page >= $total_num) {
                $next_link = '';
                $next_page_link = '';
            } else {
                if (isset($_GET['g']) and is_numeric($_GET['g'])) {
                    $nl_prepend = '<a href="?p='.($position+$articles_per_page).(empty($tags_append) ? '' : '&amp;tags='.$tags_append).'&amp;g='.$_GET['g'].'"> ';
                } else {
                    $nl_prepend = '<a href="?p='.($position+$articles_per_page).(empty($tags_append) ? '' : '&amp;tags='.$tags_append).'"> ';
                }
                $nl_append = '</a>';
                $next_link = $nl_prepend.$TEXT['NEXT'].$nl_append;
                $next_page_link = $nl_prepend.$TEXT['NEXT_PAGE'].$nl_append;
            }
            if ($position+$articles_per_page > $total_num) {
                $num_of = $position+$total_num;
            } else {
                $num_of = $position+$articles_per_page;
            }

            if($num_of>$total_num) {
                $num_of=$total_num;
            }

            $out_of = ($position+1).'-'.$num_of.' '.strtolower($TEXT['OUT_OF']).' '.$total_num;
            $of = ($position+1).'-'.$num_of.' '.strtolower($TEXT['OF']).' '.$total_num;
*/
        }   // end function getPrevNextLinks()


        /**
         *
         * @access
         * @return
         **/
        protected static function getQueryExtra()
        {
            $query_extra = '';

            // ----- filter by group? --------------------------------------------------
            if (isset($_GET['g']) and is_numeric($_GET['g'])) {
                $query_extra = " AND group_id = '".$_GET['g']."'";
            }

            // ----- filter by date?  --------------------------------------------------
            if(
                   isset($_GET['m'])      && is_numeric($_GET['m'])
                && isset($_GET['y'])      && is_numeric($_GET['y'])
                && isset($_GET['method']) && is_numeric($_GET['method'])
            ) {
                $startdate = mktime(0, 0, 0, $_GET['m'], 1, $_GET['y']);
                $enddate   = mktime(0, 0, 0, $_GET['m']+1, 1, $_GET['y']);
                switch ($_GET['method']) {
                case 0:
                    $date_option = "posted_when";
                    break;
                case 1:
                    $date_option = "published_when";
                    break;
                }
                $query_extra .= " AND ".$date_option." >= '$startdate' AND ".$date_option." < '$enddate'";
            }

            // ----- filter by tags? ---------------------------------------------------
            if(isset($_GET['tags']) && strlen($_GET['tags'])) {
                $filter_articles = array();
                $tags = mod_journal_escape_tags($_GET['tags']);
                $r = cmsbridge::db()->query(sprintf(
                    "SELECT `t2`.`article_id` FROM `%s%s` as `t1` ".
                    "JOIN `%s%s` AS `t2` ".
                    "ON `t1`.`tag_id`=`t2`.`tag_id` ".
                    "WHERE `tag` IN ('".implode("', '", $tags)."') ".
                    "GROUP BY `t2`.`article_id`",
                    self::dbprefix(), self::$tables['tags'],
                    self::dbprefix(), self::$tables['tags_articles']
                ));
                while ($row=$r->fetch()) {
                    $filter_articles[] = $row['article_id'];
                }
                if (count($filter_articles)>0) {
                    $query_extra.= " AND `t1`.`article_id` IN (".implode(',', array_values($filter_articles)).") ";
                }
            }

            return $query_extra;
        }   // end function getQueryExtra()

        /**
         *
         * @access
         * @return
         **/
        protected static function getSettings() : array
        {
            if(empty(self::$settings)) {
                self::$settings = array();
                $q = cmsbridge::db()->query(sprintf(
                    "SELECT * FROM `%s%s` WHERE `section_id`=%d",
                    cmsbridge::dbprefix(), self::$tables['settings'],
                    self::$sectionID
                ));
                if(!empty($q) && $q->rowCount()) {
                    self::$settings = $q->fetch();
                    self::$settings['thumbwidth'] = self::$settings['thumbheight'] = 100;
                    if (substr_count(self::$settings['imgthumbsize'], 'x')>0) {
                        list(
                            self::$settings['thumbwidth'],
                            self::$settings['thumbheight']
                        ) = explode('x', self::$settings['imgthumbsize'], 2);
                    }
                } else {
                    // defaults
                    self::$settings = array(
                        'append_title'      => 'N',
                        'block2'            => 'N',
                        'crop_preview'      => 'N',
                        'gallery'           => 'fotorama',
                        'header'            => '',
                        'image_loop'        => '<img src="[IMAGE]" alt="[DESCRIPTION]" title="[DESCRIPTION]" data-caption="[DESCRIPTION]" />',
                        'imgmaxheight'      => 900,
                        'imgmaxsize'        => self::getBytes(ini_get('upload_max_filesize')),
                        'imgmaxwidth'       => 900,
                        'imgthumbsize'      => '100x100',
                        'mode'              => 'advanced',
                        'articles_per_page'    => 0,
                        'tag_header'        => '',
                        'tag_footer'        => '',
                        'thumbheight'       => 100,
                        'thumbwidth'        => 100,
                        'use_second_block'  => 'N',
                        'view'              => 'default',
                        'view_order'        => 0,
                    );

                    self::$settings['footer'] = '<table class="mod_journal_table" style="visibility:[DISPLAY_PREVIOUS_NEXT_LINKS]">
<tr>
    <td class="mod_journal_table_left">[PREVIOUS_PAGE_LINK]</td>
    <td class="mod_journal_table_center">[OF]</td>
    <td class="mod_journal_table_right">[NEXT_PAGE_LINK]</td>
</tr>
</table>';

                    self::$settings['article_header'] = '<h2>[TITLE]</h2>
<div class="mod_journal_metadata">[TEXT_POSTED_BY] [DISPLAY_NAME] [TEXT_ON] [PUBLISHED_DATE] [TEXT_AT] [PUBLISHED_TIME] [TEXT_O_CLOCK] | [TEXT_LAST_CHANGED] [MODI_DATE] [TEXT_AT] [MODI_TIME] [TEXT_O_CLOCK]</div>';

                    self::$settings['article_footer'] =' <div class="mod_journal_spacer"></div>
<table class="mod_journal_table" style="visibility: [DISPLAY_PREVIOUS_NEXT_LINKS]">
<tr>
    <td class="mod_journal_table_left">[PREVIOUS_PAGE_LINK]</td>
    <td class="mod_journal_table_center"><a href="[BACK]">[TEXT_BACK]</a></td>
    <td class="mod_journal_table_right">[NEXT_PAGE_LINK]</td>
</tr>
</table>
<div class="mod_journal_tags">[TAGS]</div>';

                    self::$settings['article_loop'] = '<div class="mod_journal_group">
    <div class="mod_journal_teaserpic">
        <a href="[LINK]">[IMAGE]</a>
    </div>
    <div class="mod_journal_teasertext">
        <a href="[LINK]"><h3>[TITLE]</h3></a>
        <div class="mod_journal_metadata">[TEXT_POSTED_BY] [DISPLAY_NAME] [TEXT_ON] [PUBLISHED_DATE] [TEXT_AT] [PUBLISHED_TIME] [TEXT_O_CLOCK] </div>
            <div class="mod_journal_shorttext">
                [SHORT]
            </div>
            <div class="mod_journal_bottom">
                <div class="mod_journal_tags">[TAGS]</div>
                <div class="mod_journal_readmore" style="visibility:[SHOW_READ_MORE];"><a href="[LINK]">[TEXT_READ_MORE]</a></div>
            </div>
        </div>
    </div>
    <div class="mod_journal_spacer"><hr /></div>';

                    self::$settings['article_content'] = '<div class="mod_journal_content_short">
  [IMAGE]
  [CONTENT_SHORT]
</div>
<div class="mod_journal_content_long">[CONTENT_LONG]</div>
<div class="fotorama" data-keyboard="true" data-navposition="top" data-nav="thumbs" data-width="700" data-ratio="700/467" data-max-width="100%">
[IMAGES]
</div>
';
                    self::$settings['tag_loop'] = '<a href="[TAG_LINK]" style="float:right">[TAG]</a>';
                }
            }
            return self::$settings;
        }   // end function getSettings()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function getSizePresets() : array
        {
            return array(
                '50'  => '50x50px',
                '75'  => '75x75px',
                '100' => '100x100px',
                '125' => '125x125px',
                '150' => '150x150px',
                '220' => '200x200px',
            );
        }   // end function getSizePresets()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function getTag(int $tag_id) : array
        {
            $stmt = cmsbridge::db()->query(sprintf(
                "SELECT * FROM `%s%s` ".
                "WHERE `tag_id`=%d",
                cmsbridge::dbprefix(), self::$tables['tags'], $tag_id
            ));
            if (!empty($stmt) && $stmt->numRows() > 0) {
                return $stmt->fetch();
            }
            return array();
        }   // end function getTag()

        /**
         * get existing tags for current section
         * @param  int   $section_id
         * @param  bool  $alltags
         * @return array
         **/
        protected static function getTags(?int $section_id=null,?bool $alltags=false) : array
        {
            $tags = array();
            $where = "WHERE `section_id`=0";
            if(!empty($section_id)) {
                $where .= " OR `section_id` = '$section_id'";
            }
            if($alltags) {
                $where = null;
            }
            $query_tags = cmsbridge::db()->query(sprintf(
                "SELECT * FROM `%s%s` AS t1 " .
                "JOIN `%s%s` AS t2 " .
                "ON t1.tag_id=t2.tag_id ".
                $where, cmsbridge::dbprefix(), self::$tables['tags'],
                cmsbridge::dbprefix(), self::$tables['tags_sections']
            ));
            if (!empty($query_tags) && $query_tags->rowCount() > 0) {
                while($t = $query_tags->fetch()) {
                    $tags[$t['tag_id']] = $t;
                }
            }
            return $tags;
        }   // end function getTags()

        /**
         *
         * @access
         * @return
         **/
        protected static function getUsers() : array
        {
            $users = array();
            if(!CMSBRIDGE_CMS_BC2) {
                $query_users = cmsbridge::db()->query(sprintf(
                    "SELECT `user_id`,`username`,`display_name`,`email` FROM `%susers`",
                    cmsbridge::dbprefix()
                ));
                if(!empty($query_users) && $query_users->rowCount() > 0) {
                    while($user = $query_users->fetch()) {
                        // Insert user info into users array
                        $user_id = $user['user_id'];
                        $users[$user_id]['username'] = $user['username'];
                        $users[$user_id]['display_name'] = $user['display_name'];
                        $users[$user_id]['email'] = $user['email'];
                    }
                }
            } else {
                $users = \CAT\Helper\Users::getUserNames();
            }
            return $users;
        }   // end function getUsers()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function getViews()
        {
            $dir   = __DIR__.'/../views';
            $views = array();
            // get available views
            if(CMSBRIDGE_CMS_BC1) {
                $views = CAT_Helper_Directory::getDirectories($dir,$dir);
            } elseif(CMSBRIDGE_CMS_BC2) {
                $views = \CAT\Helper\Directory::findDirectories(
                    $dir,
                    array(
                        'remove_prefix' => true,
                    )
                );
            } else {
                $dirs = array_filter(glob(__DIR__.'/../views/*'), 'is_dir');
                foreach($dirs as $dir) {
                    $views[] = pathinfo($dir,PATHINFO_FILENAME);
                }
            }
            return $views;
        }   // end function getViews()
        
        /**
         *
         * @access protected
         * @return
         **/
        protected static function groupExists(int $groupID)
        {
            $g = self::getGroup($groupID);
            return isset($g['group_id']);
        }   // end function groupExists()
        
        /**
         * what a ***censored***: sprintf() does not work with utf8!
         *
         **/
        protected static function mb_sprintf($format, ...$args) {
            $params = $args;

            $callback = function ($length) use (&$params) {
                $value = array_shift($params);
                return strlen($value) - mb_strlen($value) + $length[0];
            };

            $format = preg_replace_callback('/(?<=%|%-)\d+(?=s)/', $callback, $format);

            return sprintf($format, ...$args);
        }

        /**
         *
         * @access protected
         * @return
         **/
        protected static function moveUpDown(int $articleID, string $direction)
        {
            if(!CMSBRIDGE_CMS_BC2) {
                $func  = 'move_'.$direction;
                $order = new order(self::$tables['articles'], 'position', 'article_id', 'section_id');
                if($order->$func($id)) {
                	return true;
                } else {
                	return false;
                }
            } else {
                $func = 'move'.ucfirst($direction);
                \CAT\Base::db()->$func($articleID, $table, 'article_id', 'position', 'section_id');
            }
        }   // end function moveUpDown()
        
        /**
         *
         * @access protected
         * @return
         **/
        protected static function printPage(string $template, array $data)
        {
            // settings
            $settings = self::getSettings(self::$sectionID);

            // shortcuts
            $sectionID = self::$sectionID;
            $pageID    = self::$pageID;

            // combine globals with $data
            $data = array_merge(self::$tpldata,$data);

            ob_start();
                include $template;
                $content = ob_get_contents();
            ob_end_clean();

            return $content;
        }   // end function printPage()
        
        /**
         *
         * @access
         * @return
         **/
        protected static function processArticle(array $article, array $users)
        {
            // make sure the settings are loaded
            self::getSettings(self::$sectionID);

            // get groups
            $groups = self::getGroups(self::$sectionID);

            // map group id to group data for easier handling
            $group_map = array();
            foreach($groups as $i => $g) {
                $group_map[$g['group_id']] = $g;
            }

            // this is for the backend only
        	$icon = '';
            $t = time();
            if ($article['published_when']<=$t && $article['published_until']==0) {
                $article['icon'] ='<span class="fa fa-fw fa-calendar-o" title="'.cmsbridge::t('Article is visible').'"></span>';
            } elseif (($article['published_when']<=$t || $article['published_when']==0) && $article['published_until']>=$t) {
                $article['icon'] ='<span class="fa fa-fw fa-calendar-check-o nwi-active" title="'.cmsbridge::t('Article is visible').'"></span>';
            } else {
                $article['icon'] ='<span class="fa fa-fw fa-calendar-times-o nwi-inactive" title="'.cmsbridge::t('Article is invisible').'"></span>';
            }

            // posting (preview) image
            if ($article['image'] != "") {
                $article_img = "<img src='".JOURNAL_IMGURL.'/'.$article['image']."' alt='".htmlspecialchars($article['title'], ENT_QUOTES | ENT_HTML401)."' />";
            } else {
                $article_img = "<img src='".JOURNAL_NOPIC."' alt='".self::t('empty placeholder')."' style='width:".self::$settings['thumbwidth']."px;' />";
            }
            $article['article_img'] = $article_img;

            // article link
            $article['article_link'] = cmsbridge::getPageLink(self::$page['route'].'/'.$article['link']);
            $article['article_path'] = str_ireplace(CMSBRIDGE_CMS_URL, CMSBRIDGE_CMS_PATH, $article['article_link']);
            $article['next_link'] = (isset($article['next_link']) && strlen($article['next_link'])>0 ? cmsbridge::getPageLink(self::$page['route'].'/'.$article['next_link']) : null);
            $article['prev_link'] = (isset($article['prev_link']) && strlen($article['prev_link'])>0 ? cmsbridge::getPageLink(self::$page['route'].'/'.$article['prev_link']) : null);

            if (isset($_GET['p']) and intval($_GET['p']) > 0) {
                $article['article_link'] .= '?p='.intval($_GET['p']);
            }
            if (isset($_GET['g']) and is_numeric($_GET['g'])) {
                // check if group exists
                $gid = intval($_GET['g']);
                if(self::groupExists($gid)) {
                    if (isset($_GET['p']) and $position > 0) {
                        $delim = '&amp;';
                    } else {
                        $delim = '?';
                    }
                    $article['article_link'] .= $delim.'g='.$gid;
                    $article['next_link'] = (strlen($article['next_link'])>0 ? $delim.'g='.$gid : null);
                    $article['prev_link'] = (strlen($article['prev_link'])>0 ? $delim.'g='.$gid : null);
                }
            }

            // publishing date
            if ($article['published_when'] === '0') {
                $article['published_when'] = time();
            }

            if ($article['published_when'] > $article['posted_when']) {
                $article['article_date'] = cmsbridge::formatDate($article['published_when']);
                $article['article_time'] = cmsbridge::formatTime($article['published_when']);
            } else {
                $article['article_date'] = cmsbridge::formatDate($article['posted_when']);
                $article['article_time'] = cmsbridge::formatTime($article['posted_when']);
            }
            $article['published_date'] = cmsbridge::formatDate($article['published_when']);
            $article['published_time'] = cmsbridge::formatTime($article['published_when']);

            $article['publishing_date'] = cmsbridge::formatDate($article['published_when']==0 ? time() : $article['published_when'])
                                     . ' ' . $article['published_time'];
            $article['publishing_end_date'] = ( $article['published_until']!=0 ? cmsbridge::formatDate($article['published_until']) : '' );
            #if(strlen($article['publishing_end_date'])>0) {
            #    $article['publishing_end_date'] .= ' ' . gmdate(TIME_FORMAT, $article['published_until']+TIMEZONE);
            #}

            if (file_exists($article['article_path'])) {
                $article['create_date'] = cmsbridge::formatDate(filemtime($article['article_path']));
                $article['create_time'] = cmsbridge::formatTime(filemtime($article['article_path']));
            } else {
                $article['create_date'] = $article['published_date'];
                $article['create_time'] = $article['published_time'];
            }

            // Get group id, title, and image
            $group_id                = $article['group_id'];
            $article['group_title']     = ( isset($group_map[$group_id]) ? $group_map[$group_id]['title'] : null );
            $article['group_image']     = ( isset($group_map[$group_id]) ? $group_map[$group_id]['image'] : null );
            $article['group_image_url'] = JOURNAL_NOPIC;
            $article['display_image']   = ($article['group_image'] == '') ? "none" : "inherit";
            $article['display_group']   = ($group_id == 0) ? 'none' : 'inherit';
            if ($article['group_image'] != "") {
                $article['group_image_url'] = $article['group_image'];
                $article['group_image'] = "<img class='mod_journal_grouppic' src='".$article['group_image_url']."' alt='".htmlspecialchars($article['group_title'], ENT_QUOTES | ENT_HTML401)."' title='".self::t('Group').": ".htmlspecialchars($article['group_title'], ENT_QUOTES | ENT_HTML401)."' />";
            }

            // fallback to group image if there's no preview image
            $article['article_or_group_image'] = (
                ($article['image'] != "")
                ? $article['article_img']
                : $article['group_image']
            );

            // user
            $article['display_name'] = isset($users[$article['posted_by']]) ? $users[$article['posted_by']]['display_name'] : '<i>'.$users[0]['display_name'] .'</i>';
            $article['username'] = isset($users[$article['posted_by']]) ? $users[$article['posted_by']]['username'] : '<i>'.$users[0]['username'] .'</i>';
            $article['email'] = isset($users[$article['posted_by']]) ? $users[$article['posted_by']]['email'] : '<i>'.$users[0]['email'] .'</i>';

            return $article;

        }   // end function processArticle()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function readArticle(int $articleID)
        {
            $article   = self::getArticle($articleID);

            // make sure the settings are loaded
            self::getSettings(self::$sectionID);

            list($vars,$default_replacements) = self::getPlaceholders();
            $article_data = array('keywords'=>array());

            // tags
            $tags = self::getAssignedTags($articleID);
            foreach ($tags as $i => $tag) {
                $tags[$i] = "<span class=\"mod_journal_tag\" id=\"mod_journal_tag_".$articleID."_".$i."\""
                          . (!empty($tag['tag_color']) ? " style=\"background-color:".$tag['tag_color']."\"" : "" ) .">"
                          . "<a href=\"".           'x'         ."?tags=".$tag['tag']."\">".$tag['tag']."</a></span>";
                if(!isset($article_data['keywords'][$tag['tag']])) {
                    $article_data['keywords'][] = htmlspecialchars($tag['tag'], ENT_QUOTES | ENT_HTML401);
                }
            }

            // gallery images
            $images = self::getImages($articleID);
            $article['article_img'] = (
                  empty($images)
                ? ''
                : self::getPreviewImage($images,htmlspecialchars($article['title'], ENT_QUOTES | ENT_HTML401),$articleID)
            );

            // page title
            $article['page_title'] = (strlen(self::$page['page_title']) ? self::$page['page_title'] : self::$page['menu_title']);
            if(self::$settings['append_title']=='Y') {
                $article['page_title'] .= JOURNAL_TITLE_SPACER . $article['title'];
            }

            // parent page link
            $page_link = cmsbridge::getPageLink(self::$page['route']);

            // previous-/next-links
            if (self::$settings['articles_per_page'] != 0) { // 0 = unlimited = no paging
                self::getPrevNextLinks();
            }

            $replacements = array_merge(
                $default_replacements,
                array_change_key_case($article,CASE_UPPER),
                array(
                    'IMAGE'           => $article['article_img'],
        			'IMAGE_URL' 	  => JOURNAL_IMGURL.$article['image'],
                    'IMAGES'          => implode("", self::renderImages($articleID,$images)),
                    'SHORT'           => $article['content_short'],
                    'LINK'            => $article['article_link'],
                    'MODI_DATE'       => $article['article_date'],
                    'MODI_TIME'       => $article['article_time'],
                    'PAGE_TITLE'      => $article['page_title'],
                    'TAGS'            => implode(" ", $tags),
                    'CONTENT'         => $article['content_short'].$article['content_long'],
                    'BACK'            => $page_link,
                    'PREVIOUS_PAGE_LINK'
                        => (strlen($article['prev_link'])>0 ? '<a href="'.$article['prev_link'].'">'.self::t('Previous article').'</a>' : null),
                    'NEXT_PAGE_LINK'
                        => (strlen($article['next_link'])>0 ? '<a href="'.$article['next_link'].'">'.self::t('Next article').'</a>' : null),
                    'DISPLAY_PREVIOUS_NEXT_LINKS'
                        => ((strlen($article['prev_link'])>0 || strlen($article['next_link'])>0) ? 'visible' : 'hidden'),
                )
            );

            // use block 2
            $article_block2 = '';
            if (self::$settings['use_second_block']=='Y') {
                // get content from article
                $article_block2 = ($article['content_block2']);
                if (empty($article_block2) && !empty(self::$settings['block2'])) {
                    // get content from settings
                    $article_block2 = self::$settings['block2'];
                }
                // replace placeholders
                $article_block2 = preg_replace_callback(
                    '~\[('.implode('|',$vars).')+\]~',
                    function($match) use($replacements) {
                        return (isset($match[1]) && isset($replacements[$match[1]]))
                            ? $replacements[$match[1]]
                            : '';
                    },
                    $article_block2
                );
                if (!defined("MODULES_BLOCK2")) {
                    define("MODULES_BLOCK2", $article_block2);
                }
                if(!defined("NEWS_BLOCK2")) {
                    define("NEWS_BLOCK2", $article_block2);
                }
                if(!defined("TOPIC_BLOCK2")) {
                    define("TOPIC_BLOCK2", $article_block2);
                }
            }

            $article_data['content'] = preg_replace_callback(
                '~\[('.implode('|',$vars).')+\]~',
                function($match) use($replacements) {
                    return (isset($match[1]) && isset($replacements[$match[1]]))
                        ? $replacements[$match[1]]
                        : '';
                },
                self::$settings['article_header'].self::$settings['article_content'].self::$settings['article_footer']
            );

            $article_data['view'] = (strlen(self::$settings['view']) ? self::$settings['view'] : 'default');

            $gal = '';
            if (strlen(self::$settings['gallery'])) {
                ob_start();
                    include JOURNAL_DIR.'/js/galleries/'.self::$settings['gallery'].'/include.tpl';
                    $gal = ob_get_contents();
                ob_end_clean();
            }

            return self::printHeaders($article_data).
                self::printPage(
                __DIR__.'/../templates/default/view.phtml',
                $article_data
            ).$gal;
        }   // end function readArticle()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function renderImages(string $articleID, array $images) : array
        {
            $rendered = array();
        	$thumbsizeraw = explode('x',self::$settings['imgthumbsize']);
            foreach($images as $i => $img) {
                $rendered[] = str_replace(
                    array(
                        '[IMAGE]',
                        '[DESCRIPTION]',
						'[THUMB]',
						'[THUMBWIDTH]',
						'[THUMBHEIGHT]',
						'[WB_URL]'
                    ),
                    array(
                        JOURNAL_IMGURL.'/'.$articleID.'/'.$img['picname'],
                        $img['picdesc'],
						JOURNAL_IMGURL.'/'.$articleID.'/thumb/'.$img['picname'],
						$thumbsizeraw[0],
						$thumbsizeraw[1],
						CMSBRIDGE_CMS_URL
                    ),
                    self::$settings['image_loop']
                );
            }
            return $rendered;
        }   // end function renderImages()
        

        /**
         *
         * @access protected
         * @return
         **/
        protected static function renderList(array $articles)
        {
            $tpl_data = array();
            $list     = array();

            // make sure the settings are loaded
            self::getSettings(self::$sectionID);

            // offset (paging)
            if (isset($_GET['p']) and is_numeric($_GET['p']) and $_GET['p'] > 0) {
                $position = intval($_GET['p']);
            } else {
                $position = 0;
            }

            list($vars,$default_replacements) = self::getPlaceholders();

            foreach($articles as $i => $article) {
                // get / render the tags
                $article['TAGS'] = self::renderTags(intval($article['article_id']));
                // get assigned images
                $images = self::getImages($article['article_id'],false);
                // number of images for later use
                $anz_article_img = count($images);
                // no "read more" link if no long content and no images
                if ( (strlen($article['content_long']) < 9) && ($anz_article_img < 1)) {
                    $article['article_link'] = '#" onclick="javascript:void(0);return false;" style="cursor:no-drop;';
                }
                // preview image
                if(!empty($anz_article_img)) {
                    foreach($images as $img) {
                        if($img['preview']=='Y') {
                            $article['article_img'] =  "<img src='".JOURNAL_IMGURL.'/'.$article['article_id'].'/thumb/'.$img['picname']."' alt='".htmlspecialchars($article['title'], ENT_QUOTES | ENT_HTML401)."' />";
                        }
                    }
                }
                // set replacements for current line
                $replacements = array_merge(
                    $default_replacements,
                    array_change_key_case($article,CASE_UPPER),
                    array(
                        'IMAGE'           => $article['article_img'],
                        'SHORT'           => $article['content_short'],
                        'LINK'            => $article['article_link'],
                        'MODI_DATE'       => $article['article_date'],
                        'MODI_TIME'       => $article['article_time'],
                        'SHOW_READ_MORE'  => (strlen($article['content_long'])<1 && ($anz_article_img<1))
                                             ? 'hidden' : 'visible',
                    )
                );

                $list[] = preg_replace_callback(
                    '~\[('.implode('|',$vars).')+\]~',
                    function($match) use($replacements) {
                        return (isset($match[1]) && isset($replacements[$match[1]]))
                            ? $replacements[$match[1]]
                            : '';
                    },
                    self::$settings['article_loop']
                );
            }

            $header = preg_replace_callback(
                '~\[('.implode('|',$vars).')+\]~',
                function($match) use($replacements) {
                    return (isset($match[1]) && isset($replacements[$match[1]]))
                        ? $replacements[$match[1]]
                        : '';
                },
                self::$settings['header']
            );

            $footer = preg_replace_callback(
                '~\[('.implode('|',$vars).')+\]~',
                function($match) use($replacements) {
                    return (isset($match[1]) && isset($replacements[$match[1]]))
                        ? $replacements[$match[1]]
                        : '';
                },
                self::$settings['footer']
            );

            $tpl_data['view']    = (strlen(self::$settings['view']) ? self::$settings['view'] : 'default');
            $tpl_data['content'] = $header . implode("\n",$list) . $footer;

            return
                self::printHeaders($tpl_data).
                self::printPage(
                    __DIR__.'/../templates/default/view.phtml',
                    $tpl_data
                );
        }   // end function renderList()
        
        /**
         *
         * @access protected
         * @return
         **/
        protected static function renderTags(int $articleID) : string
        {
            // tags
            $tags = self::getAssignedTags($articleID);
            if(is_array($tags) && count($tags)>0) {
                $article['tags'] = array();
                foreach ($tags as $i => $tag) {
                    foreach(array('tag_color','text_color','hover_color','text_hover_color') as $key) {
                        $d[$key] = ((isset($tag[$key]) && strlen($tag[$key]))
                                 ? $tag[$key] : '');
                    }
                    $article['tags'][] = str_replace(
                        array('[TAG_LINK]','[TAGCOLOR]','[TAGHOVERCOLOR]','[TEXTCOLOR]','[TEXTHOVERCOLOR]','[TAGID]','[TAG]','[PAGEID]'),
                        array('',$d['tag_color'],$d['hover_color'],$d['text_color'],$d['text_hover_color'],$i,$tag['tag'],self::$pageID),
                        self::$settings['tag_loop']
                    );
                }
                return self::$settings['tag_header']
                     . implode("\n",$article['tags'])
                     . self::$settings['tag_footer'];
            }
            return '';
        }   // end function renderTags()
        
        /**
         *
         * @access protected
         * @return
         **/
        protected static function saveArticle()
        {
            // reset errors
            self::$errors = array();

            // validate article ID
            $articleID = intval($_REQUEST['article_id']);
            if(empty($articleID) || $articleID==0) {
                self::$errors[] = self::t('Invalid ID');
                return false;
            }

            // dates
            $publishedwhen = (
                (isset($_REQUEST['publishdate']) && !empty($_REQUEST['publishdate']))
                ? cmsbridge::escapeString($_REQUEST['publishdate']) : '0'
            );
            $publisheduntil = (
                (isset($_REQUEST['enddate']) && !empty($_REQUEST['enddate']))
                ? cmsbridge::escapeString($_REQUEST['enddate']) : '0'
            );

            if(!empty($publishedwhen)) {
                $publishedwhen = strtotime($publishedwhen);
            }
            if(!empty($publisheduntil)) {
                $publisheduntil = strtotime($publisheduntil);
            }

            if(!empty($publishedwhen) && !empty($publisheduntil) && $publishedwhen > $publisheduntil) {
                self::$errors[] = self::t('Expiry date cannot be *before* starting date!');
                self::$highlighted['publishdate'] = 1;
                self::$highlighted['enddate'] = 1;
            }

            $orig  = self::getArticle($articleID);

            // title and short are mandatory
            $title = cmsbridge::escapeString($_REQUEST['title']);
            $short = cmsbridge::escapeString($_REQUEST['short']);
            if(empty($title) || empty($short)) {
                self::$errors[] = self::t('Title and short text are mandatory!');
                self::$highlighted['title'] = 1;
                self::$highlighted['short'] = 1;
            }

            if(!empty(self::$errors)) {
                return false;
            }

            // if the link is empty, generate it from the title
            $link = cmsbridge::escapeString($_REQUEST['link']);
            if(empty($link)) {
                $spacer = defined('PAGE_SPACER') ? PAGE_SPACER : '';
                $link = mb_strtolower(str_replace(" ",$spacer,$title));
                if(function_exists('page_filename')) { // WBCE und BC1 only
                    $link = page_filename($link);
                }
            }

            // validate group
            $group = cmsbridge::escapeString($_REQUEST['group']);
            if(!self::groupExists(intval($group))) {
                $group = 0;
            }

            // active state
            if(isset($_REQUEST['active'])) {
                $active = (intval($_REQUEST['active'])==1) ? 1 : 0;
            } else {
                $active = $orig['active'];
            }

            // long text (optional)
            $long = (isset($_REQUEST['long']) ? cmsbridge::escapeString($_REQUEST['long']) : '');

            // block2 (optional)
            $block2 = (isset($_REQUEST['block2']) ? cmsbridge::escapeString($_REQUEST['block2']) : '');

            // TODO: publishdate, enddate
            // Update row
            $r = cmsbridge::db()->query(self::mb_sprintf(
                "UPDATE `%s%s`"
                . " SET `group_id` = %d,"
                . " `title` = '%s',"
                . " `link` = '%s',"
                . " `content_short` = '%s',"
                . " `content_long` = '%s',"
                . " `content_block2` = '%s',"
                . " `active` = %d,"
                . " `published_when` = '%s',"
                . " `published_until` = '%s',"
                . " `posted_when` = '".time()."',"
                . " `posted_by` = '".cmsbridge::admin()->getUserID()."'"
                . " WHERE `article_id` = %d",
                cmsbridge::dbprefix(), self::$tables['articles'],
                $group, $title, $link, $short, $long, $block2,
                $active, $publishedwhen, $publisheduntil, $articleID
            ));

            if(!($r->errorCode()==0)) {
                self::$errors[] = $r->errorMessage();
                return false;
            }

            return true;
        }   // end function saveArticle()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function saveSettings()
        {
            $default = array(
                'view_order',
                'header',
                'article_loop',
                'footer',
                'article_header',
                'article_content',
                'article_footer',
                'articles_per_page',
                'gallery',
                'use_second_block',
            );
            $advanced = array(
                'append_title',
                'block2',
                'crop',
                'image_loop',
                'imgmaxsize',
                'imgmaxwidth',
                'imgmaxheight',
                'imgthumbsize',
                'view',
                'append_title',
            );

            $save_as_preset = false;
            $preset_dir = null;
            if(isset($_REQUEST['preset_name'])) {
                $preset_name = cmsbridge::escapeString($_REQUEST['preset_name']);
                $save_as_preset = true;
                $preset_dir = JOURNAL_DIR.'/views/'.$preset_name;
                if(is_dir($preset_dir)) {
                    self::$errors[] = self::t('Preset already exists').': '.$preset_name;
                    return false;
                }
            }
            
            self::getSettings();
            $mode = self::$settings['mode'];

            foreach($default as $var) {
                $$var = isset($_REQUEST[$var])
                      ? cmsbridge::escapeString($_REQUEST[$var])
                      : '';
            }
            foreach($advanced as $var) {
                if($mode == 'advanced') {
                    switch($var) {
                        case 'imgthumbsize':
                            $w = isset($_REQUEST['thumb_width'])
                               ? cmsbridge::escapeString($_REQUEST['thumb_width'])
                               : '';
                            $h = isset($_REQUEST['thumb_height'])
                               ? cmsbridge::escapeString($_REQUEST['thumb_height'])
                               : '';
                            $$var = implode('x',array($w,$h));
                            break;
                        default:
                            $$var = isset($_REQUEST[$var])
                                  ? cmsbridge::escapeString($_REQUEST[$var])
                                  : '';
                            break;
                    }
                } else {
                    $$var = self::$settings[$var];
                }
            }

            if(empty($crop)) {
                $crop = 'N';
            }

            if($save_as_preset && !empty($preset_dir)) {
                if(CMSBRIDGE_CMS_BC2) {
                    \CAT\Helper\Directory::createDirectory($preset_dir);
                    $default_css = JOURNAL_DIR.'/views/default/frontend.css';
                    if(file_exists($default_css)) {
                        copy($default_css, $preset_dir.'/frontend.css');
                    }
                    $fh = fopen($preset_dir.'/config.php','w');
                    fwrite($fh,'<'.'?'.'php'."\n");
                    fwrite($fh,'$header = \''.$header."';\n");
                    fwrite($fh,'$footer = \''.$footer."';\n");
                    fwrite($fh,'$article_loop = \''.$article_loop."';\n");
                    fwrite($fh,'$block2 = \''.$block2."';\n");
                    fwrite($fh,'$article_header = \''.$article_header."';\n");
                    fwrite($fh,'$article_content = \''.$article_content."';\n");
                    fwrite($fh,'$article_footer = \''.$article_footer."';\n");
                    fwrite($fh,'$image_loop = \''.$image_loop."';\n");
                    fclose($fh);
                }
            } else {
                // Update settings
                cmsbridge::db()->query(sprintf(
                    "UPDATE `%s%s`"
                    . " SET"
                    . " `view_order` = %d,"
                    . " `articles_per_page` = '%s',"
                    . " `header` = '%s',"
                    . " `article_loop` = '%s',"
                    . " `footer` = '%s',"
                    . " `block2` = '%s',"
                    . " `article_header` = '%s',"
                    . " `article_content` = '%s',"
                    . " `article_footer` = '%s',"
                    . " `image_loop` = '%s',"
                    . " `gallery` = '%s',"
                    . " `imgthumbsize`='%s',"
                    . " `imgmaxwidth`='%s',"
                    . " `imgmaxheight`='%s',"
                    . " `imgmaxsize`='%s',"
                    . " `crop`='%s',"
                    . " `use_second_block`='%s',"
                    . " `view`='%s',"
                    . " `append_title`='%s'"
                    . " WHERE `section_id` = '%s'",
                    cmsbridge::dbprefix(), self::$tables['settings'],
                    intval($view_order), intval($articles_per_page),
                    $header, $article_loop, $footer, $block2,
                    $article_header, $article_content, $article_footer, $image_loop,
                    $gallery, $imgthumbsize, $imgmaxwidth, $imgmaxheight,
                    $imgmaxsize, $crop, $use_second_block, $view, $append_title,
                    self::$sectionID
                ));

                self::$settings = null;
                self::getSettings();
            }
        }   // end function saveSettings()
        
        
    }

}