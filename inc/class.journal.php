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
         * @var fallback to allowed suffixes
         **/
        protected static $allowed_suffixes = array(
            'jpg','jpeg','gif','bmp','tiff','png'
        );
        /**
         * @var settings cache
         **/
        protected static $settings    = null;
        protected static $settings_db = false;
        /**
         * @var current section
         **/
        protected static $sectionID   = null;
        /**
         * @var current page
         **/
        protected static $pageID      = null;
        /**
         * @var current user
         **/
        protected static $userID      = null;
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

        /**
         *
         *
         *
         **/
        public static function initialize(array $section)
        {
            cmsbridge::initialize($section);

            self::$sectionID = intval($section['section_id']);
            self::$pageID    = cmsbridge::pagefor(self::$sectionID);
            self::$page      = cmsbridge::getPage(self::$pageID);

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
                self::$userID = cmsbridge::admin()->getUserID();
            }

            define('JOURNAL_MODDIR'  ,pathinfo(pathinfo(__DIR__,PATHINFO_DIRNAME),PATHINFO_BASENAME));
            define('JOURNAL_MODURL'  ,CMSBRIDGE_CMS_URL.'/modules/'.JOURNAL_MODDIR);

            self::$tables = array(
                'settings'      => 'mod_'.JOURNAL_MODDIR.'_settings',
                'articles'      => 'mod_'.JOURNAL_MODDIR.'_articles',
                'groups'        => 'mod_'.JOURNAL_MODDIR.'_groups',
                'images'        => 'mod_'.JOURNAL_MODDIR.'_img',
                'tags'          => 'mod_'.JOURNAL_MODDIR.'_tags',
                'articles_img'  => 'mod_'.JOURNAL_MODDIR.'_articles_img',
                'tags_sections' => 'mod_'.JOURNAL_MODDIR.'_tags_sections',
                'tags_articles' => 'mod_'.JOURNAL_MODDIR.'_tags_articles',
            );

            self::getSettings();

            define('JOURNAL_IMAGE_SUBDIR', self::$settings['image_subdir']);
            define('JOURNAL_DIR'     ,pathinfo(__DIR__,PATHINFO_DIRNAME));
            define('JOURNAL_IMGDIR'  ,CMSBRIDGE_CMS_PATH.'/'.CMSBRIDGE_MEDIA.'/'.JOURNAL_IMAGE_SUBDIR);
            define('JOURNAL_IMGURL'  ,CMSBRIDGE_CMS_URL.'/'.CMSBRIDGE_MEDIA.'/'.JOURNAL_IMAGE_SUBDIR);
            define('JOURNAL_NOPIC'   ,CMSBRIDGE_CMS_URL."/modules/".JOURNAL_MODDIR."/images/nopic.png");
            define('JOURNAL_THUMBDIR','thumbs');

            define('JOURNAL_TITLE_SPACER', ' # ');

            if(!CMSBRIDGE_CMS_BC2) {
                self::$page['route'] = self::$page['link'];
            }

            self::$tpldata = array(
                'curr_tab'      => 'p',
                'edit_url'      => cmsbridge::getRoute('/pages/edit/{id}').(CMSBRIDGE_CMS_BC2 ? '?' : '&amp;'),
                'form_edit_url' => cmsbridge::getRoute('/pages/edit/{id}'),
            );
        }

        /**
         *
         * @access public
         * @return
         **/
        public static function install()
        {

        }   // end function install()


        /**
         * called by modify.php in BC1 and WBCE and by \CAT\Backend in BC2
         *
         * @access public
         * @param  array   $section - section data from database
         * @return string
         **/
        public static function modify(array $section) : string
        {
            // tab to activate
            $curr_tab = 'p';
            $curr_tpl = 'articles';
            if (isset($_REQUEST['tab']) && in_array($_REQUEST['tab'], array('p','g','s','o','r'))) {
                $curr_tab = $_REQUEST['tab'];
            }

            $data = array(
                'is_admin'     => false,
            );

            // switch action
            switch($curr_tab) {
                // ----- group actions -----------------------------------------
                case 'g':
                    $curr_tpl = 'groups';
                    if(isset($_REQUEST['add_group']) && !empty($_REQUEST['title'])) {
                        if(!self::addGroup()) {
                            self::$errors[] = cmsbridge::t('Unable to add the group!');
                        } else {
                            self::$info[] = cmsbridge::t('Group successfully added');
                        }
                    }
                    if(isset($_REQUEST['del_group'])) {
                        if(!self::delGroup()) {
                            self::$errors[] = cmsbridge::t('Unable to delete the group!');
                        } else {
                            self::$info[] = cmsbridge::t('Group successfully deleted');
                        }
                    }
                    if(isset($_REQUEST['group_id'])) {
                        // just activate/deactivate?
                        if(isset($_REQUEST['active']) && !isset($_REQUEST['save']) && !isset($_REQUEST['saveandback'])) {
                            self::activateGroup(intval($_REQUEST['group_id']));
                        } else {
                            $return = self::editGroup(intval($_REQUEST['group_id']));
                            if(!empty($return)) {
                                return $return;
                            }
                        }
                    }
                    $data['groups']       = self::getGroups();
                    $data['num_groups']   = 0;

                    break;
                // ----- settings (options) ------------------------------------
                case 'o':
                    $curr_tpl = 'settings';
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
                // ----- posts (articles) - default view -----------------------
                case 'p':
                    if(isset($_REQUEST['mod_journal_add_article'])) {
                        return self::addArticle();
                    }
                    if(isset($_REQUEST['article_id'])) {
                        // just activate/deactivate?
                        if(isset($_REQUEST['active']) && !isset($_REQUEST['save'])) {
                            self::activateArticle(intval($_REQUEST['article_id']));
                        } else {
                            return self::editArticle(intval($_REQUEST['article_id']));
                        }
                    }
                    $data['articles'] = self::getArticles(true, '');;
                    $data['num_articles'] = count($data['articles']);

                    break;
                // ----- tags ("Stichworte") -----------------------------------
                case 's':
                    $curr_tpl = 'tags';
                    if(isset($_REQUEST['new_tag'])) {
                        self::addTag();
                    }
                    if(isset($_REQUEST['tag_id']) && !isset($_REQUEST['cancel'])) {
                        return self::editTag();
                    }
                    $data['tags'] = self::getTags(self::$sectionID);
                    break;
                case 'r':
                    $curr_tpl = 'readme';
                    break;
            }

            $data = array_merge($data, array(
                'curr_tab'     => $curr_tab,
                'curr_tpl'     => $curr_tpl,
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
                // WBCE only --->
                'importable_sections' => 0,
                // <---
            ));

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
                $data
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
            if(CMSBRIDGE_CMS_WBCE && defined('ARTICLE_ID')) {
                echo self::readArticle(ARTICLE_ID);
                return;
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
         * get article details
         * public as needed by upload.php
         *
         * @access public
         * @param  int    $articleID
         * @return array
         **/
        public static function getArticle(int $articleID) : array
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
         * @access public
         * @return
         **/
        public static function handleUpload(int $ID, ?string $for='article')
        {
            $key      = 'file';
            $settings = self::getSettings();

            switch($for) {
                case 'article':
                    $data     = self::getArticle($ID);
                    $dir      = CMSBRIDGE_MEDIA_FULLDIR.'/'.$settings['image_subdir'].'/'.$ID;
                    break;
                case 'group':
                    $data     = self::getGroup($ID);
                    $dir      = CMSBRIDGE_MEDIA_FULLDIR.'/'.$settings['image_subdir'];
                    $key      = 'image';
                    break;
            }


            if(!is_dir($dir)) {
                @make_dir($dir,0770);
            }
            if($for=='article' && !is_dir($dir.'/'.JOURNAL_THUMBDIR)) {
                @make_dir($dir.'/'.JOURNAL_THUMBDIR,0770);
            }

            if(isset($_FILES[$key]) && is_array($_FILES[$key])) {
                $picture = $_FILES[$key];
                if(empty($settings['imgmaxsize'])) {
                    $settings['imgmaxsize'] = ini_get('post_max_size');
                }
                if (isset($picture['name']) && $picture['name'] && (strlen($picture['name']) > 3))
                {
                    // check allowed file size
                    if(empty($picture['size']) || $picture['size'] > $settings['imgmaxsize']) {
                        $errors[] = self::t('The file size exceeds the limit.')
                                  . ' ' . self::t('Allowed') . ': '. self::humanize($settings['imgmaxsize'])
                                  . ( !empty($picture['size'])
                                      ? '; ' . self::t('Actual')  . ': '. self::humanize($picture['size'])
                                      : ''
                                    );
                    }
                    $ext = pathinfo($picture['name'],PATHINFO_EXTENSION);
                    // validate name
                    if(CMSBRIDGE_CMS_BC2) {
                        $name    = \CAT\Helper\Directory::sanitizeFilename($picture['name']);
                        $allowed = \CAT\Helper\Media::getAllowedFileSuffixes('image/*');
                        $name .= '.'.$ext;
                    }
                    if(CMSBRIDGE_CMS_WBCE) {
                        $name    = media_filename($picture['name']);
                        $allowed = self::$allowed_suffixes; // fallback
                    }
                    // check allowed types
                    if(!in_array($ext,$allowed)) {
                        $errors[] = self::t('Invalid type.')
                                  . ' ' . self::t('Allowed') . ': '. implode(', ',$allowed)
                                  . '; ' . self::t('Actual')  . ': '. $ext;
                    }

                    switch($for) {
                        case 'article':
                            // find free name
                            $filename = self::findFreeFilename($dir, $name);
                            // check max filename length
                            if(strlen($filename) > 256) {
                                $errors[] = self::t('Name too long.')
                                          . ' ' . self::t('Allowed') . ': 256'
                                          . '; ' . self::t('Actual')  . ': '. strlen($filename);
                            }
                            break;
                        case 'group':
                            $filename = 'group'.$ID.'.'.$ext;
                            break;
                    }

                    // any errors so far?
                    if(count(self::$errors)>0) {
                        return array(
                            'status'  => 'error',
                            'message' => implode("\n",$errors),
                        );
                    }

                    // move to media folder
                    $src = $dir.'/'.$filename;
                    if (true===move_uploaded_file($picture['tmp_name'], $src)) {
                        // can we resize the image and create a thumbnail?
                        if(extension_loaded('gd') && function_exists('getimagesize')) {
                            $crop = ($settings['crop']=='Y');
                            // resize image if necessary
                            list($w, $h) = getimagesize($src);
                            if($w > $settings['imgmaxwidth'] || $h > $settings['imgmaxheight']) {
                                self::resize($src, $src, $settings['imgmaxwidth'], $settings['imgmaxheight'], $crop);
                            }
                            if($for=='article') {
                                // create thumbnail
                                list($w, $h) = explode('x', $settings['imgthumbsize']);
                                if(!empty($w) && !empty($h)) {
                                    self::resize($src,$dir.'/'.JOURNAL_THUMBDIR.'/'.$filename, $w, $h, $crop);
                                }
                            }
                        }

                        switch($for) {
                            case 'article':
                                self::saveArticleImage($filename, $ID);
                                break;
                            case 'group':
                                self::saveGroupImage($filename, $ID);
                                break;
                        }

                        return array(
                            'status' => 'ok',
                            'message' => 'jo',
                        );
                    }
                }
            }


        }   // end function handleUpload()

        /**
         * convert bytes to human readable string
         *
         * @access public
         * @param  string $bytes
         * @return string
         **/
        public static function humanize(string $bytes) : string
        {
            $symbol          = array(' bytes', ' KB', ' MB', ' GB', ' TB');
            $exp             = 0;
            $converted_value = 0;
            $bytes           = (int)$bytes;
            if ($bytes > 0) {
                $exp = floor(log($bytes) / log(1024));
                $converted_value = ($bytes / pow(1024, floor($exp)));
            }
            return sprintf('%.2f '.$symbol[$exp], $converted_value);
        }   // end function humanize()

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
        protected static function activateArticle(int $articleID) : bool
        {
            $newval = (intval($_REQUEST['active'])==1) ? 1 : 0;
            cmsbridge::db()->query(sprintf(
                'UPDATE `%s%s` SET `active`=%d WHERE `article_id`=%d',
                cmsbridge::dbprefix(), self::$tables['articles'], $newval, $articleID
            ));
            if(cmsbridge::conn()->errorCode() != '00000') {
                self::$errors[] = cmsbridge::conn()->errorInfo();
                return false;
            }
            return true;
        }   // end function activateArticle()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function activateGroup(int $groupID) : bool
        {
            $newval = (intval($_REQUEST['active'])==1) ? 1 : 0;
            cmsbridge::db()->query(sprintf(
                'UPDATE `%s%s` SET `active`=%d WHERE `group_id`=%d',
                cmsbridge::dbprefix(), self::$tables['groups'], $newval, $groupID
            ));
            if(cmsbridge::conn()->errorCode() != '00000') {
                self::$errors[] = cmsbridge::conn()->errorInfo();
                return false;
            }
            return true;
        }   // end function activateGroup()
        /**
         *
         * @access protected
         * @return
         **/
        protected static function addGroup()
        {
            // check mandatory
            if(empty($_REQUEST['title'])) {
                self::$errors[] = self::t('Title is mandatory!');
                self::$highlighted['title'] = 1;
                return false;
            } else {
                $title = cmsbridge::escapeString(strip_tags($_REQUEST['title']));
            }

        	$active = ( isset($_REQUEST['active']) && $_REQUEST['active']=='1' )
                    ? 1 : 0;

            $pos = self::getPosition('groups', 'section_id', self::$sectionID);

            cmsbridge::db()->query(sprintf(
                "INSERT INTO `%s%s` (`section_id`,`active`,`position`,`title`) " .
                "VALUES (%d,%d,%d,'%s')",
                cmsbridge::dbprefix(), self::$tables['groups'],
                self::$sectionID, $active, $pos, $title
            ));
            return !(empty(cmsbridge::conn()->errorCode()));
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
                   "(`section_id`,`position`,`link`,`content_short`,`content_long`,`content_block2`,`active`,`posted_when`,`posted_by`) ".
                   "VALUES ('%s','0','','','','','1','%s',%d)";
            cmsbridge::db()->query(sprintf($sql, cmsbridge::dbprefix(), self::$tables['articles'], self::$sectionID, time(), self::$userID));
            if(cmsbridge::dbsuccess()) {
                $stmt = cmsbridge::db()->query('SELECT LAST_INSERT_ID() AS `id`');
                $res  = $stmt->fetch();
                $id   = $res['id'];
            }
            return self::editArticle(intval($id));
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

            if(cmsbridge::conn()->errorCode() == '00000') {
                $stmt   = cmsbridge::db()->query('SELECT LAST_INSERT_ID() AS `id`');
                $res    = $stmt->fetch();
                $tag_id = $res['id'];
            }

            if(!empty($tag_id)) {
                $tag_section_id = ($global==1 ? 0 : self::$sectionID);
                cmsbridge::db()->query(sprintf(
                    "INSERT INTO `%s%s` (`section_id`,`tag_id`) VALUES (%d, %d);",
                    cmsbridge::dbprefix(), self::$tables['tags_sections'],
                    intval($tag_section_id), intval($tag_id)
                ));
            }

            return !(empty(cmsbridge::conn()->errorCode()));
        }   // end function addTag()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function createAccessFile(int $articleID, string $file, string $time)
        {
            if(CMSBRIDGE_CMS_WBCE) {
                // The depth of the page directory in the directory hierarchy
                // '/pages' is at depth 1
                $pages_dir_depth = count(explode('/', PAGES_DIRECTORY))-1;
                // Work-out how many ../'s we need to get to the index page
                $index_location = '../';
                for ($i = 0; $i < $pages_dir_depth; $i++) {
                    $index_location .= '../';
                }

                // Write to the filename
                $content = ''.
'<?php
$page_id = '.self::$pageID.';
$section_id = '.self::$sectionID.';
$article_id = '.$articleID.';
define("ARTICLE_SECTION", $section_id);
define("ARTICLE_ID", $article_id);
require("'.$index_location.'config.php");
require(WB_PATH."/index.php");
?>';
                if ($handle = fopen($file, 'w+')) {
                    fwrite($handle, $content);
                    fclose($handle);
                    touch($file, $time);
                    change_mode($file);
                }
            }
        }   // end function createAccessFile()

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
                return !(empty(cmsbridge::conn()->errorCode()));
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
            // move up / down?
            if(isset($_REQUEST['move']) && in_array($_REQUEST['move'],array('up','down'))) {
                return self::moveUpDown($articleID, $_REQUEST['move']);
            }

            // save?
            if(isset($_REQUEST['save']) || isset($_REQUEST['saveandback'])) {
                self::saveArticle();
                if(isset($_REQUEST['saveandback'])) {
                    return '';
                }
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
            list(
                $article_data['groups'],
                $article_data['pages']
            ) = self::getAllGroups();

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
        protected static function editGroup(int $groupID) : string
        {
            // save?
            if(isset($_REQUEST['save']) || isset($_REQUEST['saveandback'])) {
                self::saveGroup();
                if(isset($_REQUEST['saveandback'])) {
                    return '';
                }
            }

            $data = self::getGroup($groupID);
            if(empty($data)) {
                self::$errors[] = self::t('invalid data');
                return '';
            }

            return self::printPage(
                __DIR__.'/../templates/default/modify_group.phtml',
                $data
            );
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

            if(!is_array($tag) || empty($tagID)) {
                self::$errors[] = self::t('No such tag');
                return false;
            }

            if(isset($_REQUEST['save_tag']) || isset($_REQUEST['saveandback'])) {
                // tag name is mandatory
                $title = cmsbridge::escapeString($_REQUEST['tag']);
                if(empty($title)) {
                    self::$errors[] = self::t('Title is mandatory!');
                    return false;
                }

                foreach(array('tag_color','text_color','hover_color','text_hover_color') as $key) {
                    $d[$key] = (isset($_REQUEST[$key]) ? cmsbridge::escapeString(strip_tags($_REQUEST[$key])) : '');
                }
                cmsbridge::db()->query(sprintf(
                    'UPDATE `%s%s` SET `tag`="%s", '.
                    '`tag_color`="%s", `hover_color`="%s", '.
                    '`text_color`="%s", `text_hover_color`="%s" '.
                    'WHERE `tag_id`=%d',
                    cmsbridge::dbprefix(), self::$tables['tags'],
                    $title, $d['tag_color'], $d['hover_color'],
                    $d['text_color'], $d['text_hover_color'], $tagID
                ));
                if(cmsbridge::conn()->errorCode() != '00000') {
                    self::$errors[] = cmsbridge::conn()->errorInfo();
                } else {
                    self::$info[] = cmsbridge::t('Tag saved');
                }

                if(isset($_REQUEST['saveandback'])) {
                    #unset($_REQUEST['tag_id']);
                    return '';
                }

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
         * if file exists, find new name by adding a number
         *
         * @access protected
         * @param  string    $dir
         * @param  string    $filename
         * @return string
         **/
        protected static function findFreeFilename(string $dir, string $filename) : string
        {
            $num = 1;
            while(file_exists($dir.'/'.$filename)) {
                $f_name = pathinfo($dir.'/'.$filename, PATHINFO_FILENAME);
                $suffix = pathinfo($dir.'/'.$filename, PATHINFO_EXTENSION);
                $filename = $f_name.'_'.$num.'.'.$suffix;
                $num++;
            }
            return $filename;
        }   // end function findFreeFilename()

        /**
         * get groups for all NWI sections on all pages
         *
         * result array:
         * page_id => array of groups
         *
         * @param   int   $section_id
         * @param   int   $page_id
         * @return  array
         **/
        protected static function getAllGroups()
        {
            $groups = array();
            $pages = array();

            // get groups for this section
            $groups[self::$pageID] = array();
            $groups[self::$pageID][self::$sectionID] = self::getGroups();

            // get all other NWI sections
/*
            $sections = mod_nwi_sections();
            foreach($sections as $sect) {
                if($sect['section_id'] != $section_id) { // skip current
                    $groups[$sect['page_id']] = array();
                    // groups
                    $groups[$sect['page_id']][$sect['section_id']] = mod_nwi_get_groups(intval($sect['section_id']));
                    // get page details for the dropdown
                    $pid = intval($sect['page_id']);
                    $page_title = "";
                    $page_details = "";
                    if ($pid != 0) { // find out the page title and print separator line
                        $page_details = $admin->get_page_details($pid);
                        if (!empty($page_details)) {
                            $page_title = isset($page_details['page_title'])
                                        ? $page_details['page_title']
                                        : ( isset($page_details['menu_title'])
                                            ? $page_details['menu_title']
                                            : "" );
                        }
                        $pages[$pid] = $page_title;
                    }
                }
            }
*/
            return array($groups,$pages);
        }   // end function mod_nwi_get_all_groups()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function getAllowedSuffixes()
        {
            $allowed = array();
            if(CMSBRIDGE_CMS_BC2) {
                $allowed = \CAT\Helper\Media::getAllowedFileSuffixes('image/*');
            }
            if(CMSBRIDGE_CMS_WBCE) {
                $allowed = self::$allowed_suffixes; // fallback
            }
            return $allowed;
        }   // end function getAllowedSuffixes()


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
        protected static function getGalleries()
        {
            $dir   = __DIR__.'/../js/galleries';
            $gal   = array();
            // get available views
            if(CMSBRIDGE_CMS_BC1) {
                $gal = CAT_Helper_Directory::getDirectories($dir,$dir);
            } elseif(CMSBRIDGE_CMS_BC2) {
                $gal = \CAT\Helper\Directory::findDirectories(
                    $dir,
                    array(
                        'remove_prefix' => true,
                    )
                );
            } else {
                $dirs = array_filter(glob($dir.'/*'), 'is_dir');
                foreach($dirs as $dir) {
                    $gal[] = pathinfo($dir,PATHINFO_FILENAME);
                }
            }
            return $gal;
        }   // end function getGalleries()

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
                $group = $query->fetch();
                $allowed = self::getAllowedSuffixes();
                $group['id_key'] = cmsbridge::admin()->getIDKEY($group['group_id']);
                $group['image']  = '';
                foreach($allowed as $suffix) {
                    if (file_exists(CMSBRIDGE_MEDIA_FULLDIR.'/'.self::$settings['image_subdir'].'/group'.$group['group_id'].'.'.$suffix)) {
                        $group['image'] = CMSBRIDGE_CMS_URL.CMSBRIDGE_MEDIA.'/'.JOURNAL_IMAGE_SUBDIR.'/group'.$group['group_id'].'.'.$suffix;
                        break;
                    }
                }
                return $group;
            }
        }   // end function getGroup()

        /**
         * get groups for section with ID $section_id
         *
         * @param   int   $section_id
         * @return
         **/
        protected static function getGroups(?int $section_id=0) : array
        {
            if(empty($section_id)) {
                $section_id = self::$sectionID;
            }
            $groups = array();
            $query  = cmsbridge::db()->query(sprintf(
                "SELECT * FROM `%s%s` " .
                "WHERE `section_id`=%d ORDER BY `position` ASC",
                cmsbridge::dbprefix(), self::$tables['groups'], $section_id
            ));
            if ($query->rowCount() > 0) {
                $allowed = self::getAllowedSuffixes();
                // Loop through groups
                while ($group = $query->fetch()) {
                    $group['id_key'] = cmsbridge::admin()->getIDKEY($group['group_id']);
                    $group['image']  = '';
                    foreach($allowed as $suffix) {
                        if (file_exists(CMSBRIDGE_MEDIA_FULLDIR.'/'.self::$settings['image_subdir'].'/group'.$group['group_id'].'.'.$suffix)) {
                            $group['image'] = CMSBRIDGE_CMS_URL.CMSBRIDGE_MEDIA.'/'.JOURNAL_IMAGE_SUBDIR.'/group'.$group['group_id'].'.'.$suffix;
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
                'BACK_LINK',                    // back to list link
                'BASE_URL',                     // base URL of the module
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
                'PREVIEW_IMAGE',                // preview image
        		'PREVIEW_IMAGE_URL',            // URL of preview image without <img src>
                'PREVIEW_IMAGE_THUMB',          // preview image
        		'PREVIEW_IMAGE_THUMB_URL',      // URL of preview image without <img src>
                'IMAGES',                       // gallery images
                'IN_GROUP',                     // text "in group"
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
'POST_TAGS',                    // Tags
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
                'AT',                           // text for "at" ("um")
                'BACK',                         // text for "back" ("zurueck")
                'MODIFIED',                     // text for "last changed" ("zuletzt geaendert")
                'NEXT',                         // text for "next article" ("naechster Beitrag")
                'O_CLOCK',                      // text for "o'clock" ("Uhr")
                'ON',                           // text for "on" ("am")
                'COMPOSED_BY',                  // text for "posted by" ("verfasst von")
                'PREV',                         // text for "previous article" ("voriger Beitrag")
                'READ_MORE',                    // text for "read more" ("Weiterlesen")
                'TITLE',                        // article title (heading)
                'USER_ID',                      // user's (who posted) ID
                'USERNAME',                     // user's (who posted) username
            );
            $default_replacements = array(
                'AT'            => self::t('at'),
                'BACK'          => self::t('Back'),
                'BASE_URL'      => CMSBRIDGE_CMS_URL.'/modules/'.JOURNAL_MODDIR,
                'IN_GROUP'      => self::t('in group'),
                'MODIFIED'      => self::t('Last modified'),
                'NEXT'          => self::t('Next article'),
                'O_CLOCK'       => self::t("o'clock"),
                'ON'            => self::t('on'),
                'COMPOSED_BY'   => self::t('Posted by'),
                'PREV'          => self::t('Previous article'),
                'READ_MORE'     => self::t('Read more'),
            );
            return array($vars,$default_replacements);
        }   // end function getPlaceholders()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function getPosition(string $table, string $field, int $ID)
        {
            $stmt = cmsbridge::db()->query(sprintf(
                "SELECT max(`position`) AS `position` FROM `%s%s` "
                . "WHERE `$s`=%d",
                cmsbridge::dbprefix(), self::$tables[$table], $field, $ID
            ));
            if(!empty($stmt)) {
                $res   = $stmt->fetch();
                $pos   = $res['position'];
                $pos++;
            } else {
                $pos = 1;
            }
            return $pos;
        }   // end function getPosition()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function getPreviewImage(array $images, string $alt, int $articleID, bool $thumb=false)
        {
            // preview image
            if(!empty($images)) {
                foreach($images as $img) {
                    if($img['preview']=='Y') {
                        return "<img src='".JOURNAL_IMGURL.'/'.$articleID.'/'.JOURNAL_THUMBDIR.'/'.$img['picname']."' alt='".$alt."' />";
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
// TODO!!!
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
                    self::$settings_db = true;
                } else {
                    // defaults
                    self::$settings = array(
                        'append_title'      => 'N',
                        'block2'            => 'N',
                        'crop'              => 'N',
                        'gallery'           => 'fotorama',
                        'header'            => '',
                        'image_loop'        => '<img src="[IMAGE]" alt="[DESCRIPTION]" title="[DESCRIPTION]" data-caption="[DESCRIPTION]" />',
                        'imgmaxheight'      => 900,
                        'imgmaxsize'        => self::getBytes(ini_get('upload_max_filesize')),
                        'imgmaxwidth'       => 900,
                        'imgthumbsize'      => '100x100',
                        'mode'              => 'advanced',
                        'articles_per_page' => 0,
                        'tag_header'        => '',
                        'tag_footer'        => '',
                        'thumbheight'       => 100,
                        'thumbwidth'        => 100,
                        'use_second_block'  => 'N',
                        'view'              => 'default',
                        'view_order'        => 0,
                        'subdir'            => 'articles',
                        'image_subdir'      => '.articles',
                    );

                    self::$settings['footer'] = '<table class="mod_journal_table" style="visibility:[DISPLAY_PREVIOUS_NEXT_LINKS]">
<tr>
    <td class="mod_journal_table_left">[PREVIOUS_PAGE_LINK]</td>
    <td class="mod_journal_table_center">[OF]</td>
    <td class="mod_journal_table_right">[NEXT_PAGE_LINK]</td>
</tr>
</table>';

                    self::$settings['article_header'] = '<h2>[TITLE]</h2>
<div class="mod_journal_metadata">[COMPOSED_BY] [DISPLAY_NAME] [ON] [PUBLISHED_DATE] [AT] [PUBLISHED_TIME] [O_CLOCK] | [MODIFIED] [MODI_DATE] [TEXT_AT] [MODI_TIME] [O_CLOCK]</div>';

                    self::$settings['article_footer'] =' <div class="mod_journal_spacer"></div>
<table class="mod_journal_table" style="visibility: [DISPLAY_PREVIOUS_NEXT_LINKS]">
<tr>
    <td class="mod_journal_table_left">[PREVIOUS_PAGE_LINK]</td>
    <td class="mod_journal_table_center"><a href="[BACK_LINK]">[BACK]</a></td>
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
        <div class="mod_journal_metadata">[COMPOSED_BY] [DISPLAY_NAME] [ON] [PUBLISHED_DATE] [AT] [PUBLISHED_TIME] [O_CLOCK] </div>
            <div class="mod_journal_shorttext">
                [SHORT]
            </div>
            <div class="mod_journal_bottom">
                <div class="mod_journal_tags">[TAGS]</div>
                <div class="mod_journal_readmore" style="visibility:[SHOW_READ_MORE];"><a href="[LINK]">[READ_MORE]</a></div>
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
                $dirs = array_filter(glob($dir.'/*'), 'is_dir');
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
            if(!file_exists($template)) {
                return 'no such template';
            }

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
        protected static function processArticle(array $article, array $users) : array
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
                $article['is_visible'] = true;
            } elseif (($article['published_when']<=$t || $article['published_when']==0) && $article['published_until']>=$t) {
                $article['is_visible'] = true;
            } else {
                $article['is_visible'] = false;
            }

            // posting (preview) image
            #if ($article['image'] != "") {
            #    $article_img = "<img src='".JOURNAL_IMGURL.'/'.$article['image']."' alt='".htmlspecialchars($article['title'], ENT_QUOTES | ENT_HTML401)."' />";
            #} else {
            #    $article_img = "<img src='".JOURNAL_NOPIC."' alt='".self::t('empty placeholder')."' style='width:".self::$settings['thumbwidth']."px;' />";
            #}
            #$article['article_img'] = $article_img;

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

            if ($article['published_when'] > $article['posted_when']) {
                $article['article_date'] = cmsbridge::formatDate($article['published_when']);
                $article['article_time'] = cmsbridge::formatTime($article['published_when']);
            } else {
                $article['article_date'] = cmsbridge::formatDate($article['posted_when']);
                $article['article_time'] = cmsbridge::formatTime($article['posted_when']);
            }
            $article['published_date'] = $article['article_date'];
            $article['published_time'] = $article['article_time'];

            #$article['publishing_date'] = cmsbridge::formatDate($article['published_when']==0 ? time() : $article['published_when'])
            #                         . ' ' . $article['published_time'];
            $article['publishing_date']     = ( $article['published_when'] !=0 )
                                            ? cmsbridge::formatDate($article['published_when']).' '.$article['published_time']
                                            : '';
            $article['publishing_end_date'] = ( $article['published_until']!=0 )
                                            ? cmsbridge::formatDate($article['published_until']).' '.cmsbridge::formatTime($article['published_until'])
                                            : '';

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
            #$article['article_or_group_image'] = (
            #    ($article['image'] != "")
            #    ? $article['article_img']
            #    : $article['group_image']
            #);

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
                    'PREVIEW_IMAGE_THUMB'     => $article['article_img'],
        			'PREVIEW_IMAGE_THUMB_URL' => JOURNAL_IMGURL.$article['article_img'],
                    'IMAGES'                  => implode("", self::renderImages($articleID,$images)),
                    'SHORT'           => $article['content_short'],
                    'LINK'            => $article['article_link'],
                    'MODI_DATE'       => $article['article_date'],
                    'MODI_TIME'       => $article['article_time'],
                    'PAGE_TITLE'      => $article['page_title'],
                    'TAGS'            => implode(" ", $tags),
                    'CONTENT'         => $article['content_short'].$article['content_long'],
                    'BACK_LINK'       => $page_link,
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
						JOURNAL_IMGURL.'/'.$articleID.'/'.JOURNAL_THUMBDIR.'/'.$img['picname'],
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
            $position = 0;
            $cnt      = count($articles);

            // make sure the settings are loaded
            self::getSettings(self::$sectionID);

            // get groups
            $groups   = self::getGroups();

            // offset (paging)
            if (isset($_GET['p']) and is_numeric($_GET['p']) and $_GET['p'] > 0) {
                $position = intval($_GET['p']);
            }

            list($vars,$default_replacements) = self::getPlaceholders();

            foreach($articles as $i => $article) {
                // get / render the tags
                $article['TAGS'] = self::renderTags(intval($article['article_id']));
                // get assigned images
                $images = self::getImages($article['article_id'],false);
                // number of images for later use
                $img_count = count($images);
                // no "read more" link if no long content and no images
                if ( (strlen($article['content_long']) < 9) && ($img_count < 1)) {
                    $article['article_link'] = '#" onclick="javascript:void(0);return false;" style="cursor:no-drop;';
                }
                // preview image
                $prev_img_found = false;
                if(!empty($img_count)) {
                    foreach($images as $img) {
                        if($img['preview']=='Y') {
                            $article['preview_image_thumb'] =  "<img src='".JOURNAL_IMGURL.'/'.$article['article_id'].'/'.JOURNAL_THUMBDIR.'/'.$img['picname']."' alt='".htmlspecialchars($article['title'], ENT_QUOTES | ENT_HTML401)."' />";
                            $article['preview_image'] =  "<img src='".JOURNAL_IMGURL.'/'.$article['article_id'].'/'.$img['picname']."' alt='".htmlspecialchars($article['title'], ENT_QUOTES | ENT_HTML401)."' />";
                            $prev_img_found = true;
                        }
                    }
                }
                // if no preview image was found, use the group image
                $g = array();
                foreach($groups as $id => $group) {
                    if($group['group_id']==$article['group_id']) {
                        $g = $group;
                    }
                }
                if(isset($g['image'])) {
                    $article['group_image'] = "<img src='".$g['image']."' alt='".htmlspecialchars($article['title'], ENT_QUOTES | ENT_HTML401)."' />";
                    if($prev_img_found == false) {
                        $article['preview_image_thumb'] = "<img src='".$g['image']."' alt='".htmlspecialchars($article['title'], ENT_QUOTES | ENT_HTML401)."' />";
                        $article['preview_image'] =  "<img src='".$g['image']."' alt='".htmlspecialchars($article['title'], ENT_QUOTES | ENT_HTML401)."' />";
                    }
                }

                // nothing at all
                if(!isset($article['preview_image_thumb'])) {
                    $article['preview_image_thumb'] = '';
                }
                if(!isset($article['preview_image'])) {
                    $article['preview_image'] = '';
                }

                // set replacements for current line
                $replacements = array_merge(
                    $default_replacements,
                    array_change_key_case($article,CASE_UPPER),
                    array(
                        'PREVIEW_IMAGE_THUMB' => $article['preview_image_thumb'],
                        'PREVIEW_IMAGE'       => $article['preview_image'],
                        'SHORT'           => $article['content_short'],
                        'LINK'            => $article['article_link'],
                        'MODI_DATE'       => $article['article_date'],
                        'MODI_TIME'       => $article['article_time'],
                        'SHOW_READ_MORE'  => (strlen($article['content_long'])<1 && ($img_count<1))
                                             ? 'hidden' : 'visible',
// !!!!! TODO: handle offsets !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
                        'DISPLAY_PREVIOUS_NEXT_LINKS'
                                          => 'hidden',
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
         * resize image
         *
         * return values:
         *    true - ok
         *    1    - image is smaller than new size
         *    2    - invalid type (unable to handle)
         *
         * @param $src    - image source
         * @param $dst    - save to
         * @param $width  - new width
         * @param $height - new height
         * @param $crop   - 0=no, 1=yes
         **/
        protected static function resize($src, $dst, $width, $height, $crop=0)
        {
            $type = strtolower(pathinfo($src,PATHINFO_EXTENSION));
            if ($type == 'jpeg') {
                $type = 'jpg';
            }
            $func = null;
            switch ($type) {
                case 'bmp':
                    $func = 'imagecreatefromwbmp';
                    break;
                case 'gif':
                    $func = 'imagecreatefromgif';
                    break;
                case 'jpg':
                    $func = 'imagecreatefromjpeg';
                    break;
                case 'png':
                    $func = 'imagecreatefrompng';
                    break;
            }

            if(!function_exists($func)) {
                return false;
            } else {
                try{
                    $img = $func($src);
                } catch (\Exception $e) {
                    return false;
                }
            }

            if(!is_resource($img)) {
                return false;
            }

            // resize
            list($w, $h) = getimagesize($src);
            if ($crop) {
                if ($w < $width or $h < $height) {
                    return 1;
                }
                $ratio = max($width/$w, $height/$h);
                $h = $height / $ratio;
                $x = ($w - $width / $ratio) / 2;
                $w = $width / $ratio;
            } else {
                if ($w < $width and $h < $height) {
                    return 1;
                }
                $ratio = min($width/$w, $height/$h);
                $width = $w * $ratio;
                $height = $h * $ratio;
                $x = 0;
            }

            $new = imagecreatetruecolor($width, $height);

            // preserve transparency
            if ($type == "gif" or $type == "png") {
                imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
                imagealphablending($new, false);
                imagesavealpha($new, true);
            }

            imagecopyresampled($new, $img, 0, 0, $x, 0, $width, $height, $w, $h);

            $r = false;
            try {
                switch ($type) {
                    case 'bmp': $r = imagewbmp($new, $dst); break;
                    case 'gif': $r = imagegif($new, $dst); break;
                    case 'jpg': $r = imagejpeg($new, $dst); break;
                    case 'png': $r = imagepng($new, $dst); break;
                }
                return true;
            } catch ( \Exception $e ) {
                return false;
            } finally {
                return $r;
            }
        }

        /**
         *
         * @access protected
         * @return
         **/
        protected static function saveArticle()
        {
            // reset errors
            self::$errors = array();
            self::$highlighted = array();

            // validate article ID
            $articleID = intval($_REQUEST['article_id']);
            if(empty($articleID) || $articleID==0) {
                self::$errors[] = self::t('Invalid ID');
                return false;
            }

            // create a query builder instance to collect the data
            $qb = cmsbridge::conn()->createQueryBuilder();
            $c  = $qb->getConnection();

            // ===== validate dates ============================================
            $from = $until = null;
            if(isset($_REQUEST['publishdate'])) {
                $from = '0';
                if(!empty($_REQUEST['publishdate'])) {
                    $from = strtotime(cmsbridge::escapeString($_REQUEST['publishdate']));
                }
                $qb->set($c->quoteIdentifier('published_when'), $from);
            }
            if(isset($_REQUEST['enddate'])) {
                $until = '0';
                if(!empty($_REQUEST['enddate'])) {
                    $until = strtotime(cmsbridge::escapeString($_REQUEST['enddate']));
                }
                $qb->set($c->quoteIdentifier('published_until'), $until);
            }
            if(
                   !empty($from)
                && !empty($until)
                && $from > $until
            ) {
                self::$errors[] = self::t('Expiry date cannot be *before* starting date!');
                self::$highlighted['publishdate'] = 1;
                self::$highlighted['enddate'] = 1;
            }

            // ===== title and short are mandatory =============================
            if(empty($_REQUEST['title']) || empty($_REQUEST['short'])) {
                self::$errors[] = self::t('Title and short text are mandatory!');
                self::$highlighted['title'] = 1;
                self::$highlighted['short'] = 1;
            } else {
                $qb->set($c->quoteIdentifier('title'), $c->quote($_REQUEST['title']));
                $qb->set($c->quoteIdentifier('content_short'), $c->quote($_REQUEST['short']));
            }

            if(!empty(self::$errors)) {
                return false;
            }

            // ===== get original data =========================================
            $orig  = self::getArticle($articleID);

            // if the link is empty, generate it from the title
            $link = cmsbridge::escapeString($_REQUEST['link']);
            if(empty($link)) {
                $spacer = defined('PAGE_SPACER') ? PAGE_SPACER : '';
                $link = mb_strtolower(str_replace(" ",$spacer,$title));
                if(function_exists('page_filename')) { // WBCE und BC1 only
                    $link = page_filename($link);
                }
            }
            $qb->set($c->quoteIdentifier('link'), $c->quote($link));

            // ===== validate other data =======================================
            $group = cmsbridge::escapeString($_REQUEST['group']);
            if(!self::groupExists(intval($group))) {
                $group = 0;
            }
            $qb->set($c->quoteIdentifier('group_id'),$group);

            // active
            if(isset($_REQUEST['active'])) {
                $active = (intval($_REQUEST['active'])==1) ? 1 : 0;
            } else {
                $active = $orig['active'];
            }
            $qb->set($c->quoteIdentifier('active'),$active);

            // long text (optional)
            $qb->set($c->quoteIdentifier('content_long'), $c->quote((isset($_REQUEST['long']) ? $_REQUEST['long'] : '')));

            // block2 (optional)
            $qb->set($c->quoteIdentifier('content_block2'), $c->quote((isset($_REQUEST['block2']) ? $_REQUEST['block2'] : '')));

            // group
            if (!empty($_REQUEST['group'])) {
                $gid_value = urldecode($_REQUEST['group']);
                $values = unserialize($gid_value);
                if (isset($values['s']) && isset($values['g']) && isset($values['p'])) {
                    if (intval($values['p'])!=0) {
                        $group_id = intval($values['g']);
                        $qb->set($c->quoteIdentifier('group_id'), intval($group_id));
// !!!!! TODO: Move to another section !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
#                        $section_id = intval($values['s']);
#                        $page_id = intval($values['p']);
                    }
                }
            }


            // ===== save ======================================================
            $qb->update(sprintf('%s%s',cmsbridge::dbprefix(), self::$tables['articles']))
               ->where('`article_id`='.$articleID);

            $r = $qb->execute();

            if(!($qb->getConnection()->errorCode()==0)) {
                self::$errors[] = $r->errorMessage();
                return false;
            }

            // ===== tags ======================================================
            // remove current tags
            cmsbridge::db()->query(sprintf(
                "DELETE FROM `%s%s` WHERE `article_id`=%d",
                cmsbridge::dbprefix(), self::$tables['tags_articles'], $articleID
            ));
            $tags = ( isset($_REQUEST['tags']) ? $_REQUEST['tags'] : null );
            // re-add marked tags
            if (is_array($tags) && count($tags)>0) {
                $existing = self::getTags(self::$sectionID,true);
                foreach (array_values($tags) as $t) {
                    $t = intval($t);
                    if (array_key_exists($t, $existing)) {
                        cmsbridge::db()->query(sprintf(
                            "INSERT IGNORE INTO `%s%s` VALUES(%d,%d)",
                            cmsbridge::dbprefix(), self::$tables['tags_articles'],
                            $articleID, $t
                        ));
                    }
                }
            }

            // image changes
            $images = self::getImages($articleID);
            if(count($images) > 0) {
                foreach ($images as $row) {
                    $row_id = $row['id'];
                    $picdesc = isset($_POST['picdesc'][$row_id])
                             ? cmsbridge::escapeString(strip_tags($_POST['picdesc'][$row_id]))
                             : '';
                    cmsbridge::db()->query(sprintf(
                        "UPDATE `%s%s` SET `picdesc`='%s' WHERE id=%d",
                        cmsbridge::dbprefix(), self::$tables['images'], $picdesc, $row_id
                    ));
                }
            }

            // BC1 / WBCE: create access file
            if(!CMSBRIDGE_CMS_BC2) {
                $dir = WB_PATH.PAGES_DIRECTORY.self::$page['link'].'/';
                if(!is_dir($dir)) {
                    @make_dir($dir,0770);
                }
                if (!is_writable($dir)) {
                    self::$errors[] = self::t('Cannot write access file!');
                    return false;
                }
                $oldfile = $dir.$orig['link'].PAGE_EXTENSION;
                $newfile = $dir.$link.PAGE_EXTENSION;
                if ($oldfile != $newfile || !file_exists($newfile)) {
                    if(file_exists($oldfile)) {
                        $file_create_time = filemtime($oldfile);
                        unlink($oldfile);
                    } else {
                        $file_create_time = time();
                    }
                    self::createAccessFile(intval($articleID), $newfile, $file_create_time);
                }
            }

            // BC2: insert route
            if(CMSBRIDGE_CMS_BC2) {
// !!!!! TODO !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// der Pfad '/'.self::$page['menu_title'].'/'.$orig['link'] ist falsch
// korrekten Pfad ermitteln
// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
                \CAT\Base::router()->updateRoute(
                    self::$pageID,
                    '/'.self::$page['menu_title'].'/'.$orig['link'],
                    '/'.self::$page['menu_title'].'/'.$link,
                    'N', 'N', 'N'
                );
            }

            return true;
        }   // end function saveArticle()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function saveArticleImage(string $filename, int $articleID)
        {
            // save image to database
            cmsbridge::db()->query(sprintf(
                "INSERT INTO `%s%s` " .
                "(`picname`) " .
                "VALUES ('%s')",
                cmsbridge::dbprefix(), self::$tables['images'], $filename
            ));
            $stmt  = cmsbridge::db()->query('SELECT LAST_INSERT_ID() AS `id`');
            $res   = $stmt->fetch();
            $imgID = $res['id'];

            // append
            $pos = self::getPosition('articles_img', 'article_id', $articleID);

            // if this is the first image in this article, set to preview
            $preview = 'N';
            $imgs = self::getImages($articleID);
            if(count($imgs)==0) {
                $preview = 'Y';
            }

            cmsbridge::db()->query(sprintf(
                "INSERT INTO `%s%s` " .
                "(`article_id`,`pic_id`,`position`,`preview`) " .
                "VALUES (%d, %d, %d, '%s')",
                cmsbridge::dbprefix(), self::$tables['articles_img'], $articleID, $imgID, $pos, $preview
            ));
        }   // end function saveArticleImage()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function saveGroup()
        {
            // reset errors
            self::$errors = array();
            self::$highlighted = array();

            // validate group ID
            $groupID = intval($_REQUEST['group_id']);
            if(empty($groupID) || $groupID==0) {
                self::$errors[] = self::t('Invalid ID');
                return false;
            }

            if(isset($_REQUEST['delete_image'])) {
                $allowed = self::getAllowedSuffixes();
                $success = false;
                foreach($allowed as $suffix) {
                    if (file_exists(CMSBRIDGE_MEDIA_FULLDIR.'/'.self::$settings['image_subdir'].'/group'.$groupID.'.'.$suffix)) {
                        $success = unlink(CMSBRIDGE_MEDIA_FULLDIR.'/'.self::$settings['image_subdir'].'/group'.$groupID.'.'.$suffix);
                        break;
                    }
                }
                if(isset($_REQUEST['ajax'])) {
                    if (!headers_sent()) {
                        header('Content-type: application/json');
                    }
                    echo json_encode(array("success"=>$success), true);
                    exit;
                }
            }

            // title is the only mandatory field
            if(empty($_REQUEST['title'])) {
                self::$errors[] = self::t('Title is mandatory!');
                self::$highlighted['title'] = 1;
                return false;
            } else {
                $title = cmsbridge::escapeString(strip_tags($_REQUEST['title']));
            }

            $orig = self::getGroup($groupID);

            // active
            if(isset($_REQUEST['active'])) {
                $active = (intval($_REQUEST['active'])==1) ? 1 : 0;
            } else {
                $active = $orig['active'];
            }

            cmsbridge::db()->query(sprintf(
                'UPDATE `%s%s` SET `title`="%s", `active`=%d WHERE `group_id`=%d',
                cmsbridge::dbprefix(), self::$tables['groups'], $title, $active, $groupID
            ));

            // image uploaded?
            if(isset($_FILES['image'])) {
                self::handleUpload($groupID,'group');
            }

        }   // end function saveGroup()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function saveGroupImage(string $filename, int $groupID)
        {

        }   // end function saveGroupImage()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function saveSettings()
        {
            // known settings
            $default = array(
                'view_order'            => 'int',
                'header'                => 'string',
                'article_loop'          => 'string',
                'footer'                => 'string',
                'article_header'        => 'string',
                'article_content'       => 'string',
                'article_footer'        => 'string',
                'articles_per_page'     => 'int',
                'gallery'               => 'string',
                'use_second_block'      => 'string',
            );
            $advanced = array(
                'append_title'          => 'string',
                'block2'                => 'string',
                'crop'                  => 'string',
                'image_loop'            => 'string',
                'imgmaxsize'            => 'int',
                'imgmaxwidth'           => 'int',
                'imgmaxheight'          => 'int',
                'imgthumbsize'          => 'string',
                'view'                  => 'string',
                'append_title'          => 'string',
                'image_subdir'          => 'string',
            );

            // make sure current settings are loaded
            self::getSettings();
            $mode = self::$settings['mode'];
            $new_gallery = $new_view = false;

            // create a query builder instance to collect the data
            $qb = cmsbridge::conn()->createQueryBuilder();
            $c  = $qb->getConnection();

            if(self::$settings_db) {
                $qb->update(sprintf('%s%s',cmsbridge::dbprefix(), self::$tables['settings']))
                   ->where('`section_id`='.self::$sectionID);
                $func = 'set';
            } else {
                $qb->insert(sprintf('%s%s',cmsbridge::dbprefix(), self::$tables['settings']))
                   ->setValue($c->quoteIdentifier('section_id'), self::$sectionID);
                $func = 'setValue';
            }

            // changed default settings
            foreach($default as $var => $type) {
                if(isset($_REQUEST[$var])) {
                    if($var=='gallery') {
                        $val = $_REQUEST['gallery'];
                        if($val != self::$settings['gallery']) {
                            $galleries = self::getGalleries();
                            if(in_array($val,$galleries)) {
                                include CMSBRIDGE_CMS_PATH.'/modules/'.JOURNAL_MODDIR.'/js/galleries/'.$val.'/settings.php';
                                $qb->$func('article_content',$c->quote($article_content));
                                $qb->$func('image_loop',$c->quote($image_loop));
                                $new_gallery = true;
                            }
                        }
                    }
                    $qb->set($c->quoteIdentifier($var), ($type=='string' ? $c->quote($_REQUEST[$var]) : intval($_REQUEST[$var])));
                }
            }

            // in advanced mode only: changed advanced settings
            if($mode == 'advanced') {
                foreach($advanced as $var => $type) {
                    if(isset($_REQUEST[$var])) {
                        if($var=='view') {
                            $val = $_REQUEST['view'];
                            if($val != self::$settings['view']) {
                                $views = self::getViews();
                                if(in_array($val,$views)) {
                                    include CMSBRIDGE_CMS_PATH.'/modules/'.JOURNAL_MODDIR.'/views/'.$val.'/config.php';
                                    $qb->$func('header',$c->quote($header));
                                    $qb->$func('article_loop',$c->quote($article_loop));
                                    $qb->$func('footer',$c->quote($footer));
                                    $qb->$func('block2',$c->quote($block2));
                                    $qb->$func('article_header',$c->quote($article_header));
                                    $qb->$func('article_content',$c->quote($article_content));
                                    $qb->$func('article_footer',$c->quote($article_footer));
                                    $new_view = true;
                                }
                            }
                        }

                        $val = null;
                        switch($var) {
                            case 'imgthumbsize':
                                $w = isset($_REQUEST['thumb_width'])
                                   ? cmsbridge::escapeString($_REQUEST['thumb_width'])
                                   : '';
                                $h = isset($_REQUEST['thumb_height'])
                                   ? cmsbridge::escapeString($_REQUEST['thumb_height'])
                                   : '';
                                $val = implode('x',array($w,$h));
                                break;
                            case 'imgmaxsize':
                                $val = intval($_REQUEST[$var]) * 1024;
                                break;
                            case 'article_content':
                            case 'image_loop':
                                // ignore if gallery has changed
                                if(!$new_gallery) {
                                    $val = $_REQUEST[$var];
                                }
                                break;
                            case 'header':
                            case 'article_loop':
                            case 'footer':
                            case 'block2':
                            case 'article_header':
                            case 'article_content':
                            case 'article_footer':
                                if(!$new_view) {
                                    $val = $_REQUEST[$var];
                                }
                                break;
                            default:
                                if(isset($_REQUEST[$var])) {
                                    $val = $_REQUEST[$var];
                                }
                                break;
                        }
                        if(!empty($val)) {
                            $qb->$func($c->quoteIdentifier($var), ($type=='string' ? $c->quote($val) : intval($val)));
                        }
                    }
                }
            }

            $r = $qb->execute();

            if(!($qb->getConnection()->errorCode()==0)) {
                self::$errors[] = $r->errorMessage();
                return false;
            }

            $save_as_preset = false;
            $preset_dir = null;
            if(isset($_REQUEST['preset_name']) && !empty($REQUEST['preset_name'])) {
                $preset_name = cmsbridge::escapeString($_REQUEST['preset_name']);
                $save_as_preset = true;
                $preset_dir = JOURNAL_DIR.'/views/'.$preset_name;
                if(is_dir($preset_dir)) {
                    self::$errors[] = self::t('Preset already exists').': '.$preset_name;
                    return false;
                }
            }

            self::$settings = null;
            self::getSettings();
        }   // end function saveSettings()


    }

}