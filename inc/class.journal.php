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
         * @var global error
         **/
        protected static $errors      = array();
        /**
         * @var table names
         **/
        protected static $tables       = array();

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
                    "DELETE FROM `%smod_journal_posts` WHERE `section_id`=%d and `title`=''",
                    cmsbridge::dbprefix(),self::$sectionID
                ));
                cmsbridge::db()->query(sprintf(
                    "DELETE FROM `%smod_journal_groups` WHERE `section_id`=%d and `title`=''",
                    cmsbridge::dbprefix(),self::$sectionID
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
                    if(isset($_REQUEST['mod_nwi_add_post'])) {
                        return self::addPost();
                    }
                    if(isset($_REQUEST['post_id'])) {
                        $ret = self::editPost(intval($_REQUEST['post_id']));
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

            $posts     = self::getPosts(true, '');
            $groups    = self::getGroups();
            $post_data = array(
                'curr_tab'   => $curr_tab,
                'is_admin'   => false,
                'posts'      => $posts,
                'num_posts'  => count($posts),
                'groups'     => $groups,
                'num_groups' => 0,
                'tags'       => self::getTags(self::$sectionID),
                'lang_map'   => array(
                    0 => cmsbridge::t('Custom'),
                    1 => cmsbridge::t('Publishing start date').', '.cmsbridge::t('descending'),
                    2 => cmsbridge::t('Publishing end date').', '.cmsbridge::t('descending'),
                    3 => cmsbridge::t('Submitted').', '.cmsbridge::t('descending'),
                    4 => cmsbridge::t('ID').', '.cmsbridge::t('descending')
                ),
                'sizes'      => self::getSizePresets(),
                'views'      => self::getViews(),
                'FTAN'       => cmsbridge::getFTAN(),
                'info'       => $info,
                'error'      => $error,
                // WBCE only --->
                'importable_sections' => 0,
                // <---
            );

            

            if(CMSBRIDGE_CMS_WBCE) {
                global $admin;
                $post_data['is_admin'] = in_array(1, $admin->get_groups_id());
            }
            if(CMSBRIDGE_CMS_BC2) {
// !!!!! TODO: permissions !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
                $post_data['is_admin'] = \CAT\Base::user()->isAuthenticated();
            }

            return self::printPage(
                __DIR__.'/../templates/default/modify.phtml',
                $post_data
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
                $post       = str_ireplace($page_name.'/', '', $this_route);
                if(strlen($post) && $post != $page_name) {
                    $postID = self::getPostByLink(urldecode($post));
                    echo self::readPost($postID);
                    return;
                }
            }

            // list
            $posts = self::getPosts(false, self::getQueryExtra(), true);
            echo self::renderList($posts);
        }   // end function view()
        
        /**
         *
         * @access public
         * @return
         **/
        public static function editPost(int $postID) : string
        {
            // just activate/deactivate?
            if(isset($_REQUEST['active']) && !isset($_REQUEST['save'])) {
                $newval = (intval($_REQUEST['active'])==1) ? 1 : 0;
                cmsbridge::db()->query(sprintf(
                    'UPDATE `%smod_journal_posts` SET `active`=%d WHERE `post_id`=%d',
                    cmsbridge::dbprefix(), $newval, $postID
                ));
                return "1";
            }

            // move up / down?
            if(isset($_REQUEST['move']) && in_array($_REQUEST['move'],array('up','down'))) {
                $result = self::moveUpDown($postID, $_REQUEST['move']);
                return "1";
            }

            // save?
            if(isset($_REQUEST['save'])) {
                self::savePost();
            }

            $post_data = self::getPost($postID);
            $post_data['linkbase'] = '';
            # in BC2, the linkbase is always the current page
            if(!CMSBRIDGE_CMS_BC2) {
                $link  = $post_data['link'];
                $parts = explode('/', $link);
                $link  = array_pop($parts);
                $post_data['linkbase'] = implode('/', $parts);
            } else {
                $post_data['linkbase'] = \CAT\Helper\Page::getLink(self::$pageID);
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
            		$post_data['jscal_format'] = 'd.m.Y'; // dd.mm.yyyy hh:mm
            		$post_data['jscal_ifformat'] = '%d.%m.%Y';
            		break;
            	case 'm/d/Y':
            	case 'm-d-Y':
            	case 'M d Y':
            	case 'm.d.Y':
            		$post_data['jscal_format'] = 'm/d/Y'; // mm/dd/yyyy hh:mm
            		$post_data['jscal_ifformat'] = '%m/%d/%Y';
            		break;
            	default:
            		$post_data['jscal_format'] = 'Y-m-d'; // yyyy-mm-dd hh:mm
            		$post_data['jscal_ifformat'] = '%Y-%m-%d';
            		break;
            }

            $post_data['images']   = self::getImages($postID,false);
            $post_data['tags']     = self::getTags(self::$sectionID,true);
            $post_data['assigned'] = self::getAssignedTags($postID);

            return self::printPage(
                __DIR__.'/../templates/default/modify_post.phtml',
                $post_data
            );
        }   // end function editPost()

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
                if(isset($data['post']['page_title'])) {
                    $content .= "<!--(REPLACE) META DESC -->\n"
                             .  '<meta name="description" content="'.$data['post']['page_title'].'"/>'."\n"
                             .  "<!--(END)-->\n";
                }
            }
            if(CMSBRIDGE_CMS_BC2) {
                // view
                \CAT\Helper\Assets::addCSS("/modules/".JOURNAL_MODDIR."/views/".$data['view']."/frontend.css");
                // gallery
                \CAT\Helper\Assets::addCSS("/modules/".JOURNAL_MODDIR."/js/galleries/".self::$settings['gallery']."/frontend.css");
                // page title
                if(isset($data['post']['page_title'])) {
                    \CAT\Helper\Page::setTitle($data['post']['page_title']);
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
                "INSERT INTO `%smod_journal_groups` (`section_id`,`active`,`position`,`title`) " .
                "VALUES (%d,%d,0,'%s')",
                cmsbridge::dbprefix(), self::$sectionID, $active, $title
            ));
            return !(cmsbridge::db()->errorCode()==0);
        }   // end function addGroup()
        
        /**
         *
         * @access protected
         * @return
         **/
        protected static function addPost()
        {
            $id  = null;
            // Insert new row into database
            $sql = "INSERT INTO `%smod_journal_posts` " .
                   "(`section_id`,`position`,`link`,`content_short`,`content_long`,`content_block2`,`active`) ".
                   "VALUES ('%s','0','','','','','1')";
            cmsbridge::db()->query(sprintf($sql, cmsbridge::dbprefix(), self::$sectionID));
            if(cmsbridge::db()->errorCode()==0) {
                $stmt = cmsbridge::db()->query('SELECT LAST_INSERT_ID() AS `id`');
                $res  = $stmt->fetch();
                $id   = $res['id'];
            }
            return self::editPost(self::$sectionID, intval($id));
        }   // end function addPost()

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
                "INSERT INTO `%smod_journal_tags` (`tag`,`tag_color`,`text_color`,`hover_color`,`text_hover_color`) " .
                "VALUES ('%s','%s','%s','%s','%s')",
                cmsbridge::dbprefix(), $title, $d['tag_color'], $d['text_color'], $d['hover_color'], $d['text_hover_color']
            ));

            if(cmsbridge::db()->errorCode()==0) {
                $stmt   = cmsbridge::db()->query('SELECT LAST_INSERT_ID() AS `id`');
                $res    = $stmt->fetch();
                $tag_id = $res['id'];
            }

            if(!empty($tag_id)) {
                $tag_section_id = ($global==1 ? 0 : self::$sectionID);
                cmsbridge::db()->query(sprintf(
                    "INSERT INTO `%smod_journal_tags_sections` (`section_id`,`tag_id`) VALUES (%d, %d);",
                    cmsbridge::dbprefix(), $tag_section_id, $tag_id
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
                // move posts to "no group"
                cmsbridge::db()->query(sprintf(
                    "UPDATE `%smod_journal_posts` SET `group_id`=0 WHERE `group_id`=%d",
                    cmsbridge::dbprefix(), $gid
                ));
                // delete group
                cmsbridge::db()->query(sprintf(
                    "DELETE FROM `%smod_journal_groups` WHERE `group_id`=%d",
                    cmsbridge::dbprefix(), $gid
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
                    'UPDATE `%smod_journal_tags` SET `tag`="%s", '.
                    '`tag_color`="%s", `hover_color`="%s", '.
                    '`text_color`="%s", `text_hover_color`="%s" '.
                    'WHERE `tag_id`=%d',
                    cmsbridge::dbprefix(), $title, $d['tag_color'], $d['hover_color'],
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
        protected static function getAssignedTags(int $postID)
        {
            $tags = array();

            $query_tags = cmsbridge::db()->query(sprintf(
                "SELECT  t1.* " .
                "FROM `%smod_journal_tags` AS t1 " .
                "JOIN `%smod_journal_tags_posts` AS t2 " .
                "ON t1.`tag_id`=t2.`tag_id` ".
                "JOIN `%smod_journal_posts` AS t3 ".
                "ON t2.`post_id`=t3.`post_id` ".
                "WHERE t2.`post_id`=%d",
                cmsbridge::dbprefix(), cmsbridge::dbprefix(), cmsbridge::dbprefix(), $postID
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
                "SELECT * FROM `%smod_journal_groups` " .
                "WHERE `group_id`=%d",
                cmsbridge::dbprefix(), $groupID
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
                "SELECT * FROM `%smod_journal_groups` " .
                "WHERE `section_id`=%d ORDER BY `position` ASC",
                cmsbridge::dbprefix(), self::$sectionID
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
        protected static function getImages(int $postID)
        {
            $settings = self::getSettings();
            $stmt = cmsbridge::db()->query(sprintf(
                "SELECT * FROM `%smod_journal_img` t1 " .
                "JOIN `%smod_journal_posts_img` t2 ".
                "ON t1.`id`=t2.`pic_id` ".
                "WHERE `t2`.`post_id`=%d " .
                "ORDER BY `position`,`id` ASC",
                cmsbridge::dbprefix(),cmsbridge::dbprefix(),intval($postID)
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
                    $order_by = "post_id";
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
                'CREATED_DATE',                 // post added
                'CREATED_TIME',                 // post added time
                'DISPLAY_GROUP',                // wether to show the group name
                'DISPLAY_IMAGE',                // wether to show the preview image
                'DISPLAY_NAME',                 // user's (who posted) display name
                'DISPLAY_PREVIOUS_NEXT_LINKS',  // wether to show prev/next
                'EMAIL',                        // user's (who posted) email address
                'GROUP_ID',                     // ID of the group the post is linked to
                'GROUP_IMAGE',                  // image of the group
                'GROUP_IMAGE_URL',              // image url
                'GROUP_TITLE',                  // group title
                'IMAGE',                        // preview image
        		'IMAGE_URL',					// URL of preview image without <img src>
                'IMAGES',                       // gallery images
                'LINK',                         // "read more" link
                'MODI_DATE',                    // post modification date
                'MODI_TIME',                    // post modification time
                'NEXT_LINK',                    // next link
                'NEXT_PAGE_LINK',               // next page link
                'OF',                           // text "of" ("von")
                'OUT_OF',                       // text "out of" ("von")
                'PAGE_TITLE',                   // page title
                'POST_ID',                      // ID of the post
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
                'TEXT_NEXT_POST',               // text for "next post" ("naechster Beitrag")
                'TEXT_O_CLOCK',                 // text for "o'clock" ("Uhr")
                'TEXT_ON',                      // text for "on" ("am")
                'TEXT_POSTED_BY',               // text for "posted by" ("verfasst von")
                'TEXT_PREV_POST',               // text for "previous post" ("voriger Beitrag")
                'TEXT_READ_MORE',               // text for "read more" ("Weiterlesen")
                'TITLE',                        // post title (heading)
                'USER_ID',                      // user's (who posted) ID
                'USERNAME',                     // user's (who posted) username
            );
            $default_replacements = array(
                'TEXT_AT'           => self::t('at'),
                'TEXT_BACK'         => self::t('Back'),
                'TEXT_LAST_CHANGED' => self::t('Last modified'),
                'TEXT_NEXT_POST'    => self::t('Next post'),
                'TEXT_O_CLOCK'      => self::t("o'clock"),
                'TEXT_ON'           => self::t('on'),
                'TEXT_POSTED_BY'    => self::t('Posted by'),
                'TEXT_PREV_POST'    => self::t('Previous post'),
                'TEXT_READ_MORE'    => self::t('Read more'),
            );
            return array($vars,$default_replacements);
        }   // end function getPlaceholders()
        
        /**
         *
         * @access protected
         * @return
         **/
        protected static function getPost(int $postID)
        {
            list($order_by,$direction) = self::getOrder(self::$sectionID);
            $prev_dir = ($direction=='DESC'?'ASC':'DESC');
            $sql = sprintf(
                "SELECT `t1`.*, " .
                "  (SELECT `link` FROM `%smod_journal_posts` AS `t2` WHERE `t2`.`$order_by` > `t1`.`$order_by` AND `section_id`=%d AND `active`=1 ORDER BY `$order_by` $prev_dir LIMIT 1 ) as `prev_link`, ".
                "  (SELECT `link` FROM `%smod_journal_posts` AS `t3` WHERE `t3`.`$order_by` < `t1`.`$order_by` AND `section_id`=%d AND `active`=1 ORDER BY `$order_by` $direction LIMIT 1 ) as `next_link` " .
                "FROM `%smod_journal_posts` AS `t1` " .
                "WHERE `post_id`=%d",
                cmsbridge::dbprefix(), self::$sectionID, cmsbridge::dbprefix(), self::$sectionID, cmsbridge::dbprefix(), $postID
            );
            $stmt = cmsbridge::db()->query($sql);
            if(!empty($stmt)) {
                $post = $stmt->fetch();
                // get users
                $users = self::getUsers();
                // add "unknown" user
                $users[0] = array(
                    'username' => 'unknown',
                    'display_name' => 'unknown',
                    'email' => ''
                );
                return self::processPost($post, $users);
            }
            return array();
        }   // end function getPost()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function getPostByLink(string $link) : int
        {
            $stmt = cmsbridge::db()->query(sprintf(
                "SELECT `post_id` FROM `%smod_journal_posts` WHERE ".
                "`link`='%s' AND `section_id`=%d",
                cmsbridge::dbprefix(), $link, self::$sectionID
            ));
            if(!empty($stmt)) {
                $post = $stmt->fetch();
                return ( isset($post['post_id']) ? intval($post['post_id']) : 0);
            }
            return 0;
        }   // end function getPostByLink()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function getPostCount()
        {
            $query_extra = self::getQueryExtra();
            $t = time();
            $sql = sprintf(
                "SELECT count(`post_id`) AS `count` " .
                "FROM `%smod_journal_posts` AS `t1` " .
                "WHERE `section_id`=%d ".
                "AND `active`=1 AND `title`!='' " .
                "AND (`published_when`  = '0' OR `published_when` <= $t) " .
                "AND (`published_until` = '0' OR `published_until` >= $t) " .
                "$query_extra ",
                cmsbridge::dbprefix(), self::$sectionID
            );
            $stmt = cmsbridge::db()->query($sql);
            if(!empty($stmt) && $stmt->numRows()>0) {
                $r = $stmt->fetch();
                return $r['count'];
            }
            return 0;
        }   // end function getPostCount()

        /**
         *
         * @access
         * @return
         **/
        protected static function getPosts(bool $is_backend, string $query_extra, bool $process=true)
        {
            $posts    = array();
            $groups   = self::getGroups(self::$sectionID);
            $t        = time();
            $limit    = '';
            $filter   = '';
            $active   = '';

            list($order_by,$direction) = self::getOrder(self::$sectionID);

            self::getSettings(self::$sectionID);

            if (self::$settings['posts_per_page'] != 0) {
                if (isset($_GET['p']) and is_numeric($_GET['p']) and $_GET['p'] >= 0) {
                    $position = intval($_GET['p']);
                } else {
                    $position = 0;
                }
                if(!$is_backend) {
                    $limit = " LIMIT $position,".self::$settings['posts_per_page'];
                }
            }

            if(!$is_backend) {
                $query_extra .= " AND `active` = '1' AND `title` != ''"
                             .  " AND (`published_when`  = '0' OR `published_when`  <= $t)"
                             .  " AND (`published_until` = '0' OR `published_until` >= $t)";
                $active      =  ' AND `active`=1';
            }

            if(isset($_GET['tags']) && strlen($_GET['tags'])) {
                $filter_posts = array();
                $tags = self::escacpeTags($_GET['tags']);
                $r    = cmsbridge::db()->query(sprintf(
                    "SELECT `t2`.`post_id` FROM `%smod_journal_tags` as `t1` ".
                    "JOIN `%smod_journal_tags_posts` AS `t2` ".
                    "ON `t1`.`tag_id`=`t2`.`tag_id` ".
                    "WHERE `tag` IN ('".implode("', '", $tags)."') ".
                    "GROUP BY `t2`.`post_id`",
                    cmsbridge::dbprefix(), cmsbridge::dbprefix()
                ));
                while ($row = $r->fetch()) {
                    $filter_posts[] = $row['post_id'];
                }
                if (count($filter_posts)>0) {
                    $filter = " AND `t1`.`post_id` IN (".implode(',', array_values($filter_posts)).") ";
                }
            }

            $prev_dir = ($direction=='DESC'?'ASC':'DESC');

            $sql = sprintf(
                "SELECT " .
                "  *, " .
                "  (SELECT COUNT(`post_id`) FROM `%smod_journal_tags_posts` AS `t2` WHERE `t2`.`post_id`=`t1`.`post_id`) as `tags`, " .
                "  (SELECT `post_id` FROM `%smod_journal_posts` AS `t3` WHERE `t3`.`$order_by` > `t1`.`$order_by` AND `section_id`='".self::$sectionID."' $active ORDER BY `$order_by` $direction LIMIT 1 ) as `next`, ".
                "  (SELECT `post_id` FROM `%smod_journal_posts` AS `t3` WHERE `t3`.`$order_by` < `t1`.`$order_by` AND `section_id`='".self::$sectionID."' $active ORDER BY `$order_by` $prev_dir LIMIT 1 ) as `prev` " .
                "FROM `%smod_journal_posts` AS `t1` WHERE `section_id`='".self::$sectionID."' $filter ".
                "$query_extra ORDER BY `$order_by` $direction $limit",
                cmsbridge::dbprefix(), cmsbridge::dbprefix(), cmsbridge::dbprefix(), cmsbridge::dbprefix()
            );

            $query_posts = cmsbridge::db()->query($sql);

            if(!empty($query_posts) && $query_posts->rowCount()>0) {
                // map group index to title
                $group_map = array();
                foreach($groups as $i => $g) {
                    $group_map[$g['group_id']] = ( empty($g['title']) ? $TEXT['NONE'] : $g['title'] );
                }
                // get users
                $users = self::getUsers();
                // add "unknown" user
                $users[0] = array(
                    'username'     => 'unknown',
                    'display_name' => 'unknown',
                    'email'        => ''
                );
                while($post = $query_posts->fetch()) {
                    if($process === true) {
                        $posts[] = self::processPost($post, $users);
                	} else {
                    	$posts[] = $post;
                    }
                }
            }

            return $posts;
        }   // end function getPosts()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function getPreviewImage(array $images, string $alt, $postID)
        {
            // preview image
            if(!empty($images)) {
                foreach($images as $img) {
                    if($img['previmg']=='Y') {
                        return "<img src='".JOURNAL_IMGURL.'/'.$postID.'/thumb/'.$img['picname']."' alt='".$alt."' />";
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
            $cnt = self::getPostCount(); // all posts in this section

/*
            $total_num = $cnt['count'];
            if ($position > 0) {
                $pl_prepend = '<a href="?p='.($position-$posts_per_page).(empty($tags_append) ? '' : '&amp;tags='.$tags_append).'">';
                if (isset($_GET['g']) and is_numeric($_GET['g'])) {
                    $pl_prepend = '<a href="?p='.($position-$posts_per_page).(empty($tags_append) ? '' : '&amp;tags='.$tags_append).'&amp;g='.$_GET['g'].'">';
                }
                $pl_append = '</a>';
                $previous_link = $pl_prepend.$TEXT['PREVIOUS'].$pl_append;
                $previous_page_link = $pl_prepend.$TEXT['PREVIOUS_PAGE'].$pl_append;
            } else {
                $previous_link = '';
                $previous_page_link = '';
            }
            if ($position + $posts_per_page >= $total_num) {
                $next_link = '';
                $next_page_link = '';
            } else {
                if (isset($_GET['g']) and is_numeric($_GET['g'])) {
                    $nl_prepend = '<a href="?p='.($position+$posts_per_page).(empty($tags_append) ? '' : '&amp;tags='.$tags_append).'&amp;g='.$_GET['g'].'"> ';
                } else {
                    $nl_prepend = '<a href="?p='.($position+$posts_per_page).(empty($tags_append) ? '' : '&amp;tags='.$tags_append).'"> ';
                }
                $nl_append = '</a>';
                $next_link = $nl_prepend.$TEXT['NEXT'].$nl_append;
                $next_page_link = $nl_prepend.$TEXT['NEXT_PAGE'].$nl_append;
            }
            if ($position+$posts_per_page > $total_num) {
                $num_of = $position+$total_num;
            } else {
                $num_of = $position+$posts_per_page;
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
                $filter_posts = array();
                $tags = mod_nwi_escape_tags($_GET['tags']);
                $r = cmsbridge::db()->query(sprintf(
                    "SELECT `t2`.`post_id` FROM `%smod_journal_tags` as `t1` ".
                    "JOIN `%smod_journal_tags_posts` AS `t2` ".
                    "ON `t1`.`tag_id`=`t2`.`tag_id` ".
                    "WHERE `tag` IN ('".implode("', '", $tags)."') ".
                    "GROUP BY `t2`.`post_id`",
                    self::dbprefix(), self::dbprefix()
                ));
                while ($row=$r->fetch()) {
                    $filter_posts[] = $row['post_id'];
                }
                if (count($filter_posts)>0) {
                    $query_extra.= " AND `t1`.`post_id` IN (".implode(',', array_values($filter_posts)).") ";
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
                    "SELECT * FROM `%smod_journal_settings` WHERE `section_id`=%d",
                    cmsbridge::dbprefix(), self::$sectionID
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
                        'posts_per_page'    => 0,
                        'tag_header'        => '',
                        'tag_footer'        => '',
                        'thumbheight'       => 100,
                        'thumbwidth'        => 100,
                        'use_second_block'  => 'N',
                        'view'              => 'default',
                        'view_order'        => 0,
                    );

                    self::$settings['footer'] = '<table class="mod_nwi_table" style="visibility:[DISPLAY_PREVIOUS_NEXT_LINKS]">
<tr>
    <td class="mod_nwi_table_left">[PREVIOUS_PAGE_LINK]</td>
    <td class="mod_nwi_table_center">[OF]</td>
    <td class="mod_nwi_table_right">[NEXT_PAGE_LINK]</td>
</tr>
</table>';

                    self::$settings['post_header'] = '<h2>[TITLE]</h2>
<div class="mod_nwi_metadata">[TEXT_POSTED_BY] [DISPLAY_NAME] [TEXT_ON] [PUBLISHED_DATE] [TEXT_AT] [PUBLISHED_TIME] [TEXT_O_CLOCK] | [TEXT_LAST_CHANGED] [MODI_DATE] [TEXT_AT] [MODI_TIME] [TEXT_O_CLOCK]</div>';

                    self::$settings['post_footer'] =' <div class="mod_nwi_spacer"></div>
<table class="mod_nwi_table" style="visibility: [DISPLAY_PREVIOUS_NEXT_LINKS]">
<tr>
    <td class="mod_nwi_table_left">[PREVIOUS_PAGE_LINK]</td>
    <td class="mod_nwi_table_center"><a href="[BACK]">[TEXT_BACK]</a></td>
    <td class="mod_nwi_table_right">[NEXT_PAGE_LINK]</td>
</tr>
</table>
<div class="mod_nwi_tags">[TAGS]</div>';

                    self::$settings['post_loop'] = '<div class="mod_nwi_group">
    <div class="mod_nwi_teaserpic">
        <a href="[LINK]">[IMAGE]</a>
    </div>
    <div class="mod_nwi_teasertext">
        <a href="[LINK]"><h3>[TITLE]</h3></a>
        <div class="mod_nwi_metadata">[TEXT_POSTED_BY] [DISPLAY_NAME] [TEXT_ON] [PUBLISHED_DATE] [TEXT_AT] [PUBLISHED_TIME] [TEXT_O_CLOCK] </div>
            <div class="mod_nwi_shorttext">
                [SHORT]
            </div>
            <div class="mod_nwi_bottom">
                <div class="mod_nwi_tags">[TAGS]</div>
                <div class="mod_nwi_readmore" style="visibility:[SHOW_READ_MORE];"><a href="[LINK]">[TEXT_READ_MORE]</a></div>
            </div>
        </div>
    </div>
    <div class="mod_nwi_spacer"><hr /></div>';

                    self::$settings['post_content'] = '<div class="mod_nwi_content_short">
  [IMAGE]
  [CONTENT_SHORT]
</div>
<div class="mod_nwi_content_long">[CONTENT_LONG]</div>
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
                '50' => '50x50px',
                '75' => '75x75px',
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
                "SELECT * FROM `%smod_journal_tags` ".
                "WHERE `tag_id`=%d",
                cmsbridge::dbprefix(), $tag_id
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
                "SELECT * FROM `%smod_journal_tags` AS t1 " .
                "JOIN `%smod_journal_tags_sections` AS t2 " .
                "ON t1.tag_id=t2.tag_id ".
                $where, cmsbridge::dbprefix(), cmsbridge::dbprefix()
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
        protected static function moveUpDown(int $postID, string $direction)
        {
            $table = 'mod_journal_posts';
            if(!CMSBRIDGE_CMS_BC2) {
                $func  = 'move_'.$direction;
                $order = new order($table, 'position', 'post_id', 'section_id');
                if($order->$func($id)) {
                	return true;
                } else {
                	return false;
                }
            } else {
                $func = 'move'.ucfirst($direction);
                \CAT\Base::db()->$func($postID, $table, 'post_id', 'position', 'section_id');
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
        protected static function processPost(array $post, array $users)
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
            if ($post['published_when']<=$t && $post['published_until']==0) {
                $post['icon'] ='<span class="fa fa-fw fa-calendar-o" title="'.cmsbridge::t('Post is visible').'"></span>';
            } elseif (($post['published_when']<=$t || $post['published_when']==0) && $post['published_until']>=$t) {
                $post['icon'] ='<span class="fa fa-fw fa-calendar-check-o nwi-active" title="'.cmsbridge::t('Post is visible').'"></span>';
            } else {
                $post['icon'] ='<span class="fa fa-fw fa-calendar-times-o nwi-inactive" title="'.cmsbridge::t('Post is invisible').'"></span>';
            }

            // posting (preview) image
            if ($post['image'] != "") {
                $post_img = "<img src='".JOURNAL_IMGURL.'/'.$post['image']."' alt='".htmlspecialchars($post['title'], ENT_QUOTES | ENT_HTML401)."' />";
            } else {
                $post_img = "<img src='".JOURNAL_NOPIC."' alt='".self::t('empty placeholder')."' style='width:".self::$settings['thumbwidth']."px;' />";
            }
            $post['post_img'] = $post_img;

            // post link
            $post['post_link'] = cmsbridge::getPageLink(self::$page['route'].'/'.$post['link']);
            $post['post_path'] = str_ireplace(CMSBRIDGE_CMS_URL, CMSBRIDGE_CMS_PATH, $post['post_link']);
            $post['next_link'] = (isset($post['next_link']) && strlen($post['next_link'])>0 ? cmsbridge::getPageLink(self::$page['route'].'/'.$post['next_link']) : null);
            $post['prev_link'] = (isset($post['prev_link']) && strlen($post['prev_link'])>0 ? cmsbridge::getPageLink(self::$page['route'].'/'.$post['prev_link']) : null);

            if (isset($_GET['p']) and intval($_GET['p']) > 0) {
                $post['post_link'] .= '?p='.intval($_GET['p']);
            }
            if (isset($_GET['g']) and is_numeric($_GET['g'])) {
                if (isset($_GET['p']) and $position > 0) {
                    $delim = '&amp;';
                } else {
                    $delim = '?';
                }
                $post['post_link'] .= $delim.'g='.$_GET['g'];
                $post['next_link'] = (strlen($post['next_link'])>0 ? $delim.'g='.$_GET['g'] : null);
                $post['prev_link'] = (strlen($post['prev_link'])>0 ? $delim.'g='.$_GET['g'] : null);
            }

            // publishing date
            if ($post['published_when'] === '0') {
                $post['published_when'] = time();
            }

            if ($post['published_when'] > $post['posted_when']) {
                $post['post_date'] = cmsbridge::formatDate($post['published_when']);
                $post['post_time'] = cmsbridge::formatTime($post['published_when']);
            } else {
                $post['post_date'] = cmsbridge::formatDate($post['posted_when']);
                $post['post_time'] = cmsbridge::formatTime($post['posted_when']);
            }
            $post['published_date'] = cmsbridge::formatDate($post['published_when']);
            $post['published_time'] = cmsbridge::formatTime($post['published_when']);

            $post['publishing_date'] = cmsbridge::formatDate($post['published_when']==0 ? time() : $post['published_when'])
                                     . ' ' . $post['published_time'];
            $post['publishing_end_date'] = ( $post['published_until']!=0 ? cmsbridge::formatDate($post['published_until']) : '' );
            #if(strlen($post['publishing_end_date'])>0) {
            #    $post['publishing_end_date'] .= ' ' . gmdate(TIME_FORMAT, $post['published_until']+TIMEZONE);
            #}

            if (file_exists($post['post_path'])) {
                $post['create_date'] = cmsbridge::formatDate(filemtime($post['post_path']));
                $post['create_time'] = cmsbridge::formatTime(filemtime($post['post_path']));
            } else {
                $post['create_date'] = $post['published_date'];
                $post['create_time'] = $post['published_time'];
            }

            // Get group id, title, and image
            $group_id                = $post['group_id'];
            $post['group_title']     = ( isset($group_map[$group_id]) ? $group_map[$group_id]['title'] : null );
            $post['group_image']     = ( isset($group_map[$group_id]) ? $group_map[$group_id]['image'] : null );
            $post['group_image_url'] = JOURNAL_NOPIC;
            $post['display_image']   = ($post['group_image'] == '') ? "none" : "inherit";
            $post['display_group']   = ($group_id == 0) ? 'none' : 'inherit';
            if ($post['group_image'] != "") {
                $post['group_image_url'] = $post['group_image'];
                $post['group_image'] = "<img class='mod_nwi_grouppic' src='".$post['group_image_url']."' alt='".htmlspecialchars($post['group_title'], ENT_QUOTES | ENT_HTML401)."' title='".htmlspecialchars($TEXT['GROUP'].": ".$post['group_title'], ENT_QUOTES | ENT_HTML401)."' />";
            }

            // fallback to group image if there's no preview image
            $post['post_or_group_image'] = (
                ($post['image'] != "")
                ? $post['post_img']
                : $post['group_image']
            );

            // user
            $post['display_name'] = isset($users[$post['posted_by']]) ? $users[$post['posted_by']]['display_name'] : '<i>'.$users[0]['display_name'] .'</i>';
            $post['username'] = isset($users[$post['posted_by']]) ? $users[$post['posted_by']]['username'] : '<i>'.$users[0]['username'] .'</i>';
            $post['email'] = isset($users[$post['posted_by']]) ? $users[$post['posted_by']]['email'] : '<i>'.$users[0]['email'] .'</i>';

            return $post;

        }   // end function processPost()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function readPost(int $postID)
        {
            $post   = self::getPost($postID);

            // make sure the settings are loaded
            self::getSettings(self::$sectionID);

            list($vars,$default_replacements) = self::getPlaceholders();
            $post_data = array('keywords'=>array());

            // tags
            $tags = self::getAssignedTags($postID);
            foreach ($tags as $i => $tag) {
                $tags[$i] = "<span class=\"mod_nwi_tag\" id=\"mod_nwi_tag_".$postID."_".$i."\""
                          . (!empty($tag['tag_color']) ? " style=\"background-color:".$tag['tag_color']."\"" : "" ) .">"
                          . "<a href=\"".           'x'         ."?tags=".$tag['tag']."\">".$tag['tag']."</a></span>";
                if(!isset($post_data['keywords'][$tag['tag']])) {
                    $post_data['keywords'][] = htmlspecialchars($tag['tag'], ENT_QUOTES | ENT_HTML401);
                }
            }

            // gallery images
            $images = self::getImages($postID);
            $post['post_img'] = (
                  empty($images)
                ? ''
                : self::getPreviewImage($images,htmlspecialchars($post['title'], ENT_QUOTES | ENT_HTML401),$postID)
            );

            // page title
            $post['page_title'] = (strlen(self::$page['page_title']) ? self::$page['page_title'] : self::$page['menu_title']);
            if(self::$settings['append_title']=='Y') {
                $post['page_title'] .= JOURNAL_TITLE_SPACER . $post['title'];
            }

            // parent page link
            $page_link = cmsbridge::getPageLink(self::$page['route']);

            // previous-/next-links
            if (self::$settings['posts_per_page'] != 0) { // 0 = unlimited = no paging
                self::getPrevNextLinks();
            }

            $replacements = array_merge(
                $default_replacements,
                array_change_key_case($post,CASE_UPPER),
                array(
                    'IMAGE'           => $post['post_img'],
        			'IMAGE_URL' 	  => JOURNAL_IMGURL.$post['image'],
                    'IMAGES'          => implode("", self::renderImages($postID,$images)),
                    'SHORT'           => $post['content_short'],
                    'LINK'            => $post['post_link'],
                    'MODI_DATE'       => $post['post_date'],
                    'MODI_TIME'       => $post['post_time'],
                    'PAGE_TITLE'      => $post['page_title'],
                    'TAGS'            => implode(" ", $tags),
                    'CONTENT'         => $post['content_short'].$post['content_long'],
                    'BACK'            => $page_link,
                    'PREVIOUS_PAGE_LINK'
                        => (strlen($post['prev_link'])>0 ? '<a href="'.$post['prev_link'].'">'.self::t('Previous post').'</a>' : null),
                    'NEXT_PAGE_LINK'
                        => (strlen($post['next_link'])>0 ? '<a href="'.$post['next_link'].'">'.self::t('Next post').'</a>' : null),
                    'DISPLAY_PREVIOUS_NEXT_LINKS'
                        => ((strlen($post['prev_link'])>0 || strlen($post['next_link'])>0) ? 'visible' : 'hidden'),
                )
            );

            // use block 2
            $post_block2 = '';
            if (self::$settings['use_second_block']=='Y') {
                // get content from post
                $post_block2 = ($post['content_block2']);
                if (empty($post_block2) && !empty(self::$settings['block2'])) {
                    // get content from settings
                    $post_block2 = self::$settings['block2'];
                }
                // replace placeholders
                $post_block2 = preg_replace_callback(
                    '~\[('.implode('|',$vars).')+\]~',
                    function($match) use($replacements) {
                        return (isset($match[1]) && isset($replacements[$match[1]]))
                            ? $replacements[$match[1]]
                            : '';
                    },
                    $post_block2
                );
                if (!defined("MODULES_BLOCK2")) {
                    define("MODULES_BLOCK2", $post_block2);
                }
                if(!defined("NEWS_BLOCK2")) {
                    define("NEWS_BLOCK2", $post_block2);
                }
                if(!defined("TOPIC_BLOCK2")) {
                    define("TOPIC_BLOCK2", $post_block2);
                }
            }

            $post_data['content'] = preg_replace_callback(
                '~\[('.implode('|',$vars).')+\]~',
                function($match) use($replacements) {
                    return (isset($match[1]) && isset($replacements[$match[1]]))
                        ? $replacements[$match[1]]
                        : '';
                },
                self::$settings['post_header'].self::$settings['post_content'].self::$settings['post_footer']
            );

            $post_data['view'] = (strlen(self::$settings['view']) ? self::$settings['view'] : 'default');

            $gal = '';
            if (strlen(self::$settings['gallery'])) {
                ob_start();
                    include JOURNAL_DIR.'/js/galleries/'.self::$settings['gallery'].'/include.tpl';
                    $gal = ob_get_contents();
                ob_end_clean();
            }

            return self::printHeaders($post_data).
                self::printPage(
                __DIR__.'/../templates/default/view.phtml',
                $post_data
            ).$gal;
        }   // end function readPost()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function renderImages(string $postID, array $images) : array
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
                        JOURNAL_IMGURL.'/'.$postID.'/'.$img['picname'],
                        $img['picdesc'],
						JOURNAL_IMGURL.'/'.$postID.'/thumb/'.$img['picname'],
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
        protected static function renderList(array $posts)
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

            foreach($posts as $i => $post) {
                // get / render the tags
                $post['TAGS'] = self::renderTags(intval($post['post_id']));
                // get assigned images
                $images = self::getImages($post['post_id'],false);
                // number of images for later use
                $anz_post_img = count($images);
                // no "read more" link if no long content and no images
                if ( (strlen($post['content_long']) < 9) && ($anz_post_img < 1)) {
                    $post['post_link'] = '#" onclick="javascript:void(0);return false;" style="cursor:no-drop;';
                }
                // preview image
                if(!empty($anz_post_img)) {
                    foreach($images as $img) {
                        if($img['preview']=='Y') {
                            $post['post_img'] =  "<img src='".JOURNAL_IMGURL.'/'.$post['post_id'].'/thumb/'.$img['picname']."' alt='".htmlspecialchars($post['title'], ENT_QUOTES | ENT_HTML401)."' />";
                        }
                    }
                }
                // set replacements for current line
                $replacements = array_merge(
                    $default_replacements,
                    array_change_key_case($post,CASE_UPPER),
                    array(
                        'IMAGE'           => $post['post_img'],
                        'SHORT'           => $post['content_short'],
                        'LINK'            => $post['post_link'],
                        'MODI_DATE'       => $post['post_date'],
                        'MODI_TIME'       => $post['post_time'],
                        'SHOW_READ_MORE'  => (strlen($post['content_long'])<1 && ($anz_post_img<1))
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
                    self::$settings['post_loop']
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
        protected static function renderTags(int $postID) : string
        {
            // tags
            $tags = self::getAssignedTags($postID);
            if(is_array($tags) && count($tags)>0) {
                $post['tags'] = array();
                foreach ($tags as $i => $tag) {
                    foreach(array('tag_color','text_color','hover_color','text_hover_color') as $key) {
                        $d[$key] = ((isset($tag[$key]) && strlen($tag[$key]))
                                 ? $tag[$key] : '');
                    }
                    $post['tags'][] = str_replace(
                        array('[TAG_LINK]','[TAGCOLOR]','[TAGHOVERCOLOR]','[TEXTCOLOR]','[TEXTHOVERCOLOR]','[TAGID]','[TAG]','[PAGEID]'),
                        array('',$d['tag_color'],$d['hover_color'],$d['text_color'],$d['text_hover_color'],$i,$tag['tag'],self::$pageID),
                        self::$settings['tag_loop']
                    );
                }
                return self::$settings['tag_header']
                     . implode("\n",$post['tags'])
                     . self::$settings['tag_footer'];
            }
            return '';
        }   // end function renderTags()
        
        /**
         *
         * @access protected
         * @return
         **/
        protected static function savePost()
        {
            $postID = intval($_REQUEST['post_id']);
            if(empty($postID) || $postID==0) {
                self::$errors[] = self::t('Invalid ID');
                return false;
            }
            // get orig. post
            $orig = self::getPost($postID);
            // title and short are mandatory
            $title = cmsbridge::escapeString($_REQUEST['title']);
            $short = cmsbridge::escapeString($_REQUEST['short']);
            if(empty($title) || empty($short)) {
                self::$errors[] = self::t('Title and short text are mandatory!');
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
                $active = $post['active'];
            }
            // long text
            $long = (isset($_REQUEST['long']) ? cmsbridge::escapeString($_REQUEST['long']) : '');
            // optianal block2
            $block2 = (isset($_REQUEST['block2']) ? cmsbridge::escapeString($_REQUEST['block2']) : '');

            // TODO: publishdate, enddate
            // Update row
            cmsbridge::db()->query(self::mb_sprintf(
                "UPDATE `%smod_journal_posts`"
                . " SET `group_id` = %d,"
                . " `title` = '%s',"
                . " `link` = '%s',"
                . " `content_short` = '%s',"
                . " `content_long` = '%s',"
                . " `content_block2` = '%s',"
#                . " `image` = '%s',"
                . " `active` = %d,"
#                . " `published_when` = '$publishedwhen',"
#                . " `published_until` = '$publisheduntil',"
                . " `posted_when` = '".time()."',"
                . " `posted_by` = '".cmsbridge::admin()->getUserID()."'"
                . " WHERE `post_id` = %d",
                cmsbridge::dbprefix(),
                $group, $title, $link, $short, $long, $block2,
                $active, $postID
            ));
/*
Array
(
    [section_id] => 76
    [page_id] => 88
    [post_id] => 107
    [title] => Manuell rein in die DB
    [link] => manuellreinindieDB
    [group] => a%3A3%3A%7Bs%3A1%3A%22g%22%3Bs%3A0%3A%22%22%3Bs%3A1%3A%22s%22%3Bi%3A76%3Bs%3A1%3A%22p%22%3Bs%3A2%3A%2288%22%3B%7D
    [active] => 1
    [publishdate] => 21.04.2020 17:39
    [enddate] =>
    [short] => iga
    [long] => aga
    [save] => Speichern
)
*/
        }   // end function savePost()

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
                'post_loop',
                'footer',
                'post_header',
                'post_content',
                'post_footer',
                'posts_per_page',
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
                    fwrite($fh,'$post_loop = \''.$post_loop."';\n");
                    fwrite($fh,'$block2 = \''.$block2."';\n");
                    fwrite($fh,'$post_header = \''.$post_header."';\n");
                    fwrite($fh,'$post_content = \''.$post_content."';\n");
                    fwrite($fh,'$post_footer = \''.$post_footer."';\n");
                    fwrite($fh,'$image_loop = \''.$image_loop."';\n");
                    fclose($fh);
                }
            } else {
                // Update settings
                cmsbridge::db()->query(sprintf(
                    "UPDATE `%s%s`"
                    . " SET"
                    . " `view_order` = %d,"
                    . " `posts_per_page` = '%s',"
                    . " `header` = '%s',"
                    . " `post_loop` = '%s',"
                    . " `footer` = '%s',"
                    . " `block2` = '%s',"
                    . " `post_header` = '%s',"
                    . " `post_content` = '%s',"
                    . " `post_footer` = '%s',"
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
                    intval($view_order), intval($posts_per_page),
                    $header, $post_loop, $footer, $block2,
                    $post_header, $post_content, $post_footer, $image_loop,
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