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
         * @var use editarea for syntax highlighting in settings dialog
         **/
        public    static $use_editarea = false;
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

            if (isset($section['section_id'])) {
                self::$sectionID = intval($section['section_id']);
                self::$pageID    = (isset($section['page_id']) ? intval($section['page_id']) : cmsbridge::pagefor(self::$sectionID));
                self::$page      = cmsbridge::getPage(self::$pageID);
            }

            define('JOURNAL_MODDIR'  , pathinfo(pathinfo(__DIR__,PATHINFO_DIRNAME),PATHINFO_BASENAME));

            self::$tables = array(
                'settings'      => 'mod_'.JOURNAL_MODDIR.'_settings',
                'articles'      => 'mod_'.JOURNAL_MODDIR.'_articles',
                'groups'        => 'mod_'.JOURNAL_MODDIR.'_groups',
                'images'        => 'mod_'.JOURNAL_MODDIR.'_img',
                'tags'          => 'mod_'.JOURNAL_MODDIR.'_tags',
                'articles_img'  => 'mod_'.JOURNAL_MODDIR.'_articles_img',
                'tags_sections' => 'mod_'.JOURNAL_MODDIR.'_tags_sections',
                'tags_articles' => 'mod_'.JOURNAL_MODDIR.'_tags_articles',
                'sortorder'     => 'mod_'.JOURNAL_MODDIR.'_sortorder',
            );

            self::getSettings();

            define('JOURNAL_IMAGE_SUBDIR', self::$settings['image_subdir']);
            define('JOURNAL_DIR'     , pathinfo(__DIR__,PATHINFO_DIRNAME));
            define('JOURNAL_IMGURL'  , CMSBRIDGE_CMS_URL.'/'.CMSBRIDGE_MEDIA.'/'.JOURNAL_IMAGE_SUBDIR);
            define('JOURNAL_NOPIC'   , CMSBRIDGE_CMS_URL."/modules/".JOURNAL_MODDIR."/images/nopic.png");
            define('JOURNAL_THUMBDIR', 'thumbs');
            define('JOURNAL_TITLE_SPACER', ' # ');
            define('JOURNAL_MODURL'  , CMSBRIDGE_CMS_URL.'/modules/'.JOURNAL_MODDIR);

            if(CMSBRIDGE_CMS_BC2) {
                $lang_path = \CAT\Helper\Directory::sanitizePath(
                    implode('/',array(
                        CAT_ENGINE_PATH,
                        CAT_MODULES_FOLDER,
                        $section['module'],
                        CAT_LANGUAGES_FOLDER))
                );
                if (is_dir($lang_path)) {
                    \CAT\Base::lang()->addPath($lang_path,'JOURNAL_LANG');
                }
                define('PAGE_SPACER','_');
                define('PAGE_EXTENSION','');
                self::$userID = \CAT\Base::user()->getID();
                define('JOURNAL_IMGDIR'  ,CAT_PATH.'/'.CMSBRIDGE_MEDIA.'/'.JOURNAL_IMAGE_SUBDIR);

            }
            if(CMSBRIDGE_CMS_WBCE || CMSBRIDGE_CMS_BC1) {
                cmsbridge::setLangVar('JOURNAL_LANG');
                self::$userID = cmsbridge::admin()->getUserID();
                define('JOURNAL_IMGDIR'  ,CMSBRIDGE_CMS_PATH.'/'.CMSBRIDGE_MEDIA.'/'.JOURNAL_IMAGE_SUBDIR);
            }

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
                    if(isset($_REQUEST['import'])) {
                        self::Import();
                    }
                    if(isset($_REQUEST['article_id'])) {
                        // just activate/deactivate?
                        if(isset($_REQUEST['active']) && !isset($_REQUEST['save']) && !isset($_REQUEST['saveandback'])) {
                            self::activateArticle(intval($_REQUEST['article_id']));
                        } else {
                            $content = self::editArticle(intval($_REQUEST['article_id']));
                            if(strlen($content)) {
                                return $content;
                            }
                        }
                    }
                    $data['articles'] = self::getArticles(self::$sectionID,true, '');;
                    $data['num_articles'] = count($data['articles']);

                    break;
                // ----- tags ("Stichworte") -----------------------------------
                case 's':
                    $curr_tpl = 'tags';
                    if(isset($_REQUEST['new_tag'])) {
                        self::addTag();
                    }
                    if(isset($_REQUEST['tag_id']) && !isset($_REQUEST['cancel'])) {
                        if(isset($_REQUEST['delete'])) {
                            cmsbridge::db()->query(sprintf(
                                'DELETE FROM `%s%s` WHERE `tag_id`=%d',
                                cmsbridge::dbprefix(), self::$tables['tags'], 
                                intval($_REQUEST['tag_id'])
                            ));
                        } else {
                            return self::editTag();
                        }
                    }
                    $data['tags'] = self::getTags(self::$sectionID);
                    break;
                case 'r':
                    $curr_tpl = 'readme';
                    break;
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

            $data = array_merge($data, array(
                'curr_tab'            => $curr_tab,
                'curr_tpl'            => $curr_tpl,
                'lang_map'            => array(
                    0 => cmsbridge::t('Custom'),
                    1 => cmsbridge::t('Publishing start date').', '.cmsbridge::t('descending'),
                    2 => cmsbridge::t('Publishing end date').', '.cmsbridge::t('descending'),
                    3 => cmsbridge::t('Submitted').', '.cmsbridge::t('descending'),
                    4 => cmsbridge::t('ID').', '.cmsbridge::t('descending')
                ),
                'sizes'               => self::getSizePresets(),
                'views'               => self::getViews(),
                'FTAN'                => cmsbridge::getFTAN(),
                'importable_sections' => self::getImportableSections(),
                'orders'              => self::getSortorders(),
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
        }   // end function modify()

        /**
         *
         * @access public
         * @return
         **/
        public static function view(array $section)
        {
            self::$sectionID = intval($section['section_id']);
            self::$pageID    = cmsbridge::pagefor(self::$sectionID);

            // list
            $articles = self::getArticles(self::$sectionID, false, self::getQueryExtra(), true);

            if(CMSBRIDGE_CMS_BC2) { // resolve route
                $this_route = \CAT\Base::router()->getRoute();
                $page_name  = \CAT\Helper\Page::properties(self::$pageID, 'menu_title');
                $article    = str_ireplace($page_name.'/', '', $this_route);
                if(strlen($article) && $article != $page_name) {
                    $articleID = self::getArticleByLink(urldecode($article));
                    echo self::readArticle($articleID,$articles);
                    return;
                }
            }
            if(defined('ARTICLE_ID')) {
                echo self::readArticle(ARTICLE_ID,$articles);
                return;
            }

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
        public static function checkAccessFile(int $articleID, string $file)
        {
            if(!file_exists($file)) {
                self::createAccessFile($articleID,$file,time());
            }
        }   // end function checkAccessFile()

        /**
         *
         * @access protected
         * @return
         **/
        public static function createAccessFile(int $articleID, string $file, string $time, ?int $pageID=0, ?int $sectionID=0)
        {
            if(empty($pageID)) { $pageID = self::$pageID; }
            if(empty($sectionID)) { $sectionID = self::$sectionID; }

            if(CMSBRIDGE_CMS_WBCE || CMSBRIDGE_CMS_BC1) {
                // Write to the filename
                $content = ''.
'<?php
$page_id = '.$pageID.';
$section_id = '.$sectionID.';
$article_id = '.$articleID.';
define("ARTICLE_SECTION", $section_id);
define("ARTICLE_ID", $article_id);
$dir = __DIR__;
$max = 9;
$curr = 0;
while(!file_exists($dir.\'/config.php\')) {
    $dir = pathinfo($dir,PATHINFO_DIRNAME);
    $curr++;
    if($curr>$max) { break; }
}
require_once $dir."/config.php";
require_once WB_PATH."/index.php";
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
         * if file exists, find new name by adding a number
         *
         * @access protected
         * @param  string    $dir
         * @param  string    $filename
         * @return string
         **/
        public static function findFreeFilename(string $dir, string $filename) : string
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
                "SELECT * FROM `%s%s` AS `t1` WHERE `article_id`=%d",
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
         * @access
         * @return
         **/
        public static function getArticles(int $sectionID, ?bool $is_backend=false, ?string $query_extra='', ?bool $process=true)
        {
            $articles = array();
            $groups   = self::getGroups($sectionID);
            $t        = time();
            $limit    = '';
            $active   = '';

            list($order_by,$direction) = self::getOrder($sectionID);

            // make sure settings are loaded
            self::getSettings($sectionID);

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

            $add_to_query   = array();
            if(strlen($query_extra)) {
                $add_to_query[] = $query_extra;
            }
            if(!$is_backend) {
                $add_to_query[] = "`active` = '1' AND `title` != ''";
                $add_to_query[] = "(`published_when`  = '0' OR `published_when`  <= $t)";
                $add_to_query[] = "(`published_until` = '0' OR `published_until` >= $t)";
                $active         = ' AND `active`=1';
                if(!isset($_GET['tags']) || !strlen($_GET['tags'])) {
                    $add_to_query[] = "`t1`.`section_id`=$sectionID";
                }
            } else {
                $add_to_query[] = '`t1`.`section_id`='.$sectionID;
            }
            $query_extra = implode(' AND ', $add_to_query);

            $sql = sprintf(
                "SELECT " .
                "  `t1`.*, `t5`.`page_id`, `t5`.`menu_title` AS `parent_page_title`, " .
                "  (SELECT COUNT(`article_id`) FROM `%s%s` AS `t2` WHERE `t2`.`article_id`=`t1`.`article_id`) as `tags` " .
                "FROM `%s%s` AS `t1` ".
                "JOIN `%ssections` AS `t4` ON `t1`.`section_id`=`t4`.`section_id` ".
                ( CMSBRIDGE_CMS_BC2
                    ? sprintf(
                        "JOIN `%spages_sections` AS `t6` ON `t1`.`section_id`=`t6`.`section_id` ".
                        "JOIN `%spages` AS `t5` ON `t6`.`page_id`=`t5`.`page_id` ",
                        cmsbridge::dbprefix(),cmsbridge::dbprefix()
                    )
                    : sprintf(
                        "JOIN `%spages` AS `t5` ON `t4`.`page_id`=`t5`.`page_id` ",
                        cmsbridge::dbprefix()
                    )
                ).
                (strlen($query_extra) ? "WHERE $query_extra " : '').
                "ORDER BY `$order_by` $direction $limit",
                cmsbridge::dbprefix(), self::$tables['tags_articles'],
                cmsbridge::dbprefix(), self::$tables['articles'],
                cmsbridge::dbprefix(), self::$tables['articles'],
                cmsbridge::dbprefix(), self::$tables['articles'],
                cmsbridge::dbprefix()
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
         * get groups for section with ID $section_id
         *
         * @param   int   $section_id
         * @return
         **/
        public static function getGroups(?int $section_id=0) : array
        {
            if(empty($section_id)) {
                $section_id = self::$sectionID;
                $settings   = self::$settings;
            } else {
                $settings   = self::getSettingsForSection($section_id);
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
                        if (file_exists(CMSBRIDGE_MEDIA_FULLDIR.'/'.$settings['image_subdir'].'/group'.$group['group_id'].'.'.$suffix)) {
                            $group['image'] = CMSBRIDGE_CMS_URL.CMSBRIDGE_MEDIA.'/'.$settings['image_subdir'].'/group'.$group['group_id'].'.'.$suffix;
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
        public static function getImages(int $articleID)
        {
            $settings = self::getSettings();
            $sql = sprintf(
                "SELECT * FROM `%s%s` t1 " .
                "JOIN `%s%s` t2 ".
                "ON t1.`pic_id`=t2.`pic_id` ".
                "WHERE `t2`.`article_id`=%d " .
                "ORDER BY `position`,`t1`.`pic_id` ASC",
                cmsbridge::dbprefix(), self::$tables['images'],
                cmsbridge::dbprefix(), self::$tables['articles_img'],
                intval($articleID)
            );
            $stmt = cmsbridge::db()->query($sql);
            if(is_object($stmt) && $stmt->rowCount()>0) {
                $images = $stmt->fetchAll();
                if(is_array($images) && count($images)>0) {
                    $img_base_path = CMSBRIDGE_CMS_PATH.CMSBRIDGE_MEDIA.'/'.JOURNAL_IMAGE_SUBDIR;
                    $img_base_url  = CMSBRIDGE_CMS_URL.CMSBRIDGE_MEDIA.'/'.JOURNAL_IMAGE_SUBDIR;
                    foreach($images as $i => $image) {
                        $images[$i]['img_url'] = $img_base_url.'/'.self::$sectionID.'/'.$image["picname"];
                        $images[$i]['thumb_url'] = (
                              file_exists($img_base_path.'/'.self::$sectionID.'/'.JOURNAL_THUMBDIR.'/'.$image["picname"])
                            ? $img_base_url.'/'.self::$sectionID.'/'.JOURNAL_THUMBDIR.'/'.$image["picname"]
                            : $images[$i]['img_url']
                        );
                    }
                    return $images;
                }
            }
            return array();
        }   // end function getImages()

        /**
         *
         * @access protected
         * @return
         **/
        public static function getSettingsForSection(int $sectionID)
        {
            $q = cmsbridge::db()->query(sprintf(
                "SELECT * FROM `%s%s` WHERE `section_id`=%d",
                cmsbridge::dbprefix(), self::$tables['settings'],
                $sectionID
            ));
            // get settings from DB
            if(!empty($q) && $q->rowCount()) {
                $settings = $q->fetch();
                $settings_db = true;
            // no settings, set some defaults
            } else {
                // defaults
                $settings = array(
                    '___defaults___'    => true,
                    'append_title'      => 'N',
                    'block2'            => 'N',
                    'crop'              => 'N',
                    'gallery'           => 'fotorama',
                    'header'            => '',
                    'image_loop'        => '<img src="[IMAGE]" alt="[DESCRIPTION]" title="[DESCRIPTION]" data-caption="[DESCRIPTION]" />',
                    'imgmaxsize'        => self::getBytes(ini_get('upload_max_filesize')),
                    'imgmaxwidth'       => 4096,
                    'imgmaxheight'      => 4096,
                    'imgthumbwidth'     => 150,
                    'imgthumbheight'    => 150,
                    'mode'              => 'advanced',
                    'articles_per_page' => 0,
                    'tag_header'        => '',
                    'tag_footer'        => '',
                    'use_second_block'  => 'N',
                    'view'              => 'default',
                    'view_order'        => 0,
                    'subdir'            => 'articles',
                    'image_subdir'      => '.articles',
                );

                $settings['footer'] = '<table class="mod_journal_table" style="visibility:[DISPLAY_PREVIOUS_NEXT_LINKS]">
<tr>
<td class="mod_journal_table_left">[PREVIOUS_PAGE_LINK]</td>
<td class="mod_journal_table_center">[OF]</td>
<td class="mod_journal_table_right">[NEXT_PAGE_LINK]</td>
</tr>
</table>';

                $settings['article_header'] = '<h2>[TITLE]</h2>
<div class="mod_journal_metadata">[COMPOSED_BY] [DISPLAY_NAME] [ON] [PUBLISHED_DATE] [AT] [PUBLISHED_TIME] [O_CLOCK] | [MODIFIED] [MODI_DATE] [TEXT_AT] [MODI_TIME] [O_CLOCK]</div>';

                $settings['article_footer'] =' <div class="mod_journal_spacer"></div>
<table class="mod_journal_table" style="visibility: [DISPLAY_PREVIOUS_NEXT_LINKS]">
<tr>
<td class="mod_journal_table_left">[PREVIOUS_PAGE_LINK]</td>
<td class="mod_journal_table_center"><a href="[BACK_LINK]">[BACK]</a></td>
<td class="mod_journal_table_right">[NEXT_PAGE_LINK]</td>
</tr>
</table>
<div class="mod_journal_tags">[TAGS]</div>';

                $settings['article_loop'] = '<div class="mod_journal_group">
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

                $settings['article_content'] = '<div class="mod_journal_content_short">
[IMAGE]
[CONTENT_SHORT]
</div>
<div class="mod_journal_content_long">[CONTENT_LONG]</div>
<div class="fotorama" data-keyboard="true" data-navposition="top" data-nav="thumbs" data-width="700" data-ratio="700/467" data-max-width="100%">
[IMAGES]
</div>
';
                $settings['tag_loop'] = '<a href="[TAG_LINK]" style="float:right">[TAG]</a>';
            }
            return $settings;
        }   // end function getSettingsForSection()

        /**
         *
         * @access public
         * @return
         **/
        public static function getTableName(string $for)
        {
            return (
                isset(self::$tables[$for])
                ? self::$tables[$for]
                : null
            );
        }   // end function getTableName()

        /**
         * get existing tags for current section
         * @param  int   $section_id
         * @param  bool  $alltags
         * @return array
         **/
        public static function getTags(?int $section_id=null,?bool $alltags=false) : array
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
         * @access public
         * @return
         **/
        public static function handleUpload(int $ID, ?string $for='article')
        {
            $key      = 'file';
            $settings = self::getSettings();
            $dir      = null;
            $thumbdir = null;

            switch($for) {
                case 'article':
                    $data     = self::getArticle($ID);
                    $dir      = CMSBRIDGE_MEDIA_FULLDIR.'/'.$settings['image_subdir'].'/'.$data['section_id'];
                    $thumbdir = $dir.'/'.JOURNAL_THUMBDIR;
                    break;
                case 'group':
                    $data     = self::getGroup($ID);
                    $dir      = CMSBRIDGE_MEDIA_FULLDIR.'/'.$settings['image_subdir'];
                    $thumbdir = null;
                    $key      = 'image';
                    break;
            }

            // make sure the target directories exist
            if(!is_dir(CMSBRIDGE_MEDIA_FULLDIR.'/'.$settings['image_subdir'])) {
                self::createDir(CMSBRIDGE_MEDIA_FULLDIR.'/'.$settings['image_subdir']);
            }
            if(!is_dir($dir)) {
                self::createDir($dir);
            }
            if(!empty($thumbdir) && !is_dir($thumbdir)) {
                self::createDir($thumbdir);
            }

            // ===== Code for plupload =========================================

            // HTTP headers for no cache etc
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Cache-Control: no-store, no-cache, must-revalidate");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");

            // Get parameters
            $chunk     = isset($_REQUEST["chunk"])  ? intval($_REQUEST["chunk"])  : 0;
            $chunks    = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 0;
            $fileName  = isset($_REQUEST["name"])   ? $_REQUEST["name"]           : '';

            // some other settings
            $targetDir        = ini_get("upload_tmp_dir").'/'."plupload";
            $finalDir         = $dir;
            $cleanupTargetDir = true; // Remove old files
            $maxFileAge       = 5 * 3600;   // Temp file age in seconds

            @set_time_limit(5 * 60);  // max. 5 minutes execution time

            // Clean the fileName for security reasons
            $fileName = preg_replace('/[^\w\._]+/', '_', $fileName);

            // Make sure the fileName is unique but only if chunking is disabled
            if ($chunks < 2 && file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName)) {
            	$fileName = self::findFreeFilename($targetDir,$fileName);
            }

            $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

            // make sure the temp dir exists
            if (!file_exists($targetDir)) {
            	self::createDir($targetDir);
            }

            // Remove old temp files
            if ($cleanupTargetDir && is_dir($targetDir) && ($dir = opendir($targetDir))) {
            	while (($file = readdir($dir)) !== false) {
            		$tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;
            		// Remove temp file if it is older than the max age and is not the current file
            		if (preg_match('/\.part$/', $file) && (filemtime($tmpfilePath) < time() - $maxFileAge) && ($tmpfilePath != "{$filePath}.part")) {
            			@unlink($tmpfilePath);
            		}
            	}
            	closedir($dir);
            }

            // Look for the content type header
            if (isset($_SERVER["HTTP_CONTENT_TYPE"])) {
            	$contentType = $_SERVER["HTTP_CONTENT_TYPE"];
            }
            if (isset($_SERVER["CONTENT_TYPE"])) {
            	$contentType = $_SERVER["CONTENT_TYPE"];
            }

            // Handle non multipart uploads (older WebKit versions didn't support multipart in HTML5)
            if (strpos($contentType, "multipart") !== false) {
            	if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
            		// Open temp file
            		$out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
            		if ($out) {
            			// Read binary input stream and append it to temp file
            			$in = fopen($_FILES['file']['tmp_name'], "rb");
            			if ($in) {
            				while ($buff = fread($in, 4096)) {
            					fwrite($out, $buff);
                            }
            			} else {
                            return array(
                                'jsonrpc' => '2.0',
                                'error' => array(
                                    'code' => 101,
                                    'message' => 'Failed to open input stream.',
                                ),
                                'id' => 'id',
                            );
                        }
            			fclose($in);
            			fclose($out);
            			@unlink($_FILES['file']['tmp_name']);
            		} else {
                        return array(
                            'jsonrpc' => '2.0',
                            'error' => array(
                                'code' => 101,
                                'message' => 'Failed to open input stream.',
                            ),
                            'id' => 'id',
                        );
                    }
            	} else {
                    return array(
                        'jsonrpc' => '2.0',
                        'error' => array(
                            'code' => 102,
                            'message' => 'Failed to open output stream.',
                        ),
                        'id' => 'id',
                    );
                }
            } else {
            	// Open temp file
            	$out = fopen("{$filePath}.part", $chunk == 0 ? "wb" : "ab");
            	if ($out) {
            		// Read binary input stream and append it to temp file
            		$in = fopen("php://input", "rb");
            		if ($in) {
            			while ($buff = fread($in, 4096)) {
            				fwrite($out, $buff);
                        }
            		} else {
                        return array(
                            'jsonrpc' => '2.0',
                            'error' => array(
                                'code' => 101,
                                'message' => 'Failed to open input stream.',
                            ),
                            'id' => 'id',
                        );
                    }
            		fclose($in);
            		fclose($out);
            	} else {
                    return array(
                        'jsonrpc' => '2.0',
                        'error' => array(
                            'code' => 102,
                            'message' => 'Failed to open output stream.',
                        ),
                        'id' => 'id',
                    );
                }
            }

            // Check if file has been uploaded
            if (!$chunks || $chunk == $chunks - 1) {
            	// Strip the temp .part suffix off
            	rename("{$filePath}.part", $filePath);
            }

            // Moves the file from $targetDir to $finalDir after receiving the final chunk
            if($chunk == ($chunks-1)){
                // sanitize finalDir
                $finalDir = str_replace(array('\\','//'), '/', $finalDir);
                $imagefile = $finalDir.'/'.$fileName;
                rename($targetDir.'/'.$fileName, $imagefile);
                switch($for) {
                    case 'article':
// !!!!! TODO: Fehlerbehandlung !!!
                        cmsbridge::db()->query(sprintf(
                            'INSERT INTO `%s%s` (`section_id`,`picname`) VALUES (%d,"%s")',
                            cmsbridge::dbprefix(), self::$tables['images'],
                            $data['section_id'], $fileName
                        ));
                        if(cmsbridge::dbsuccess()) {
                            $picID = cmsbridge::getLastInsertId();
                            // get max position = append
                            $pos = self::getPosition('articles_img','article_id',intval($ID));
                            cmsbridge::db()->query(sprintf(
                                'INSERT INTO `%s%s` (`article_id`,`pic_id`,`position`) VALUES (%d,%d,%d)',
                                cmsbridge::dbprefix(), self::$tables['articles_img'],
                                intval($ID), intval($picID), intval($pos)
                            ));
                        }
                        // resize image; this may be done by plupload, but we
                        // want to be sure; the resize function will return
                        // result value 1 if the image is not resized
                        self::resize(
                            $imagefile, // src
                            $imagefile, // dst
                            intval($settings['imgmaxwidth']),
                            intval($settings['imgmaxheight']),
                            ($settings['crop']=='Y' ? 1 : 0)
                        );
                        // create thumb
                        $thumbdir = str_replace(array('\\','//'), '/', $thumbdir);
                        self::resize(
                            $imagefile, // src
                            $thumbdir.'/'.$fileName, // dst
                            intval($settings['imgthumbwidth']),
                            intval($settings['imgthumbheight']),
                            ($settings['crop']=='Y' ? 1 : 0)
                        );
                        break;
                    case 'group':
                        $data     = self::getGroup($ID);
                        $dir      = CMSBRIDGE_MEDIA_FULLDIR.'/'.$settings['image_subdir'];
                        $key      = 'image';
                        break;
                }
            }

            return array(
                'jsonrpc' => '2.0',
                'result' => null,
                'id' => 'id',
            );
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
            if(CMSBRIDGE_CMS_BC1) {
                \CAT_Helper_Page::addCSS("/modules/".JOURNAL_MODDIR."/views/".$data['view']."/frontend.css");
                \CAT_Helper_Page::addCSS("/modules/".JOURNAL_MODDIR."/js/galleries/".self::$settings['gallery']."/frontend.css");
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
            if(!cmsbridge::dbsuccess()) {
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
            if(!cmsbridge::dbsuccess()) {
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
            return cmsbridge::dbsuccess();
        }   // end function addGroup()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function addArticle()
        {
            $id  = null;
            // get position (append)
            $pos = self::getPosition('articles','section_id',self::$sectionID);
            // Insert new row into database
            $sql = "INSERT INTO `%s%s` " .
                   "(`section_id`,`position`,`link`,`content_short`,`content_long`,`content_block2`,`active`,`posted_when`,`posted_by`) ".
                   "VALUES ('%s','%d','','','','','1','%s',%d)";
            cmsbridge::db()->query(sprintf(
                $sql,
                cmsbridge::dbprefix(), self::$tables['articles'],
                self::$sectionID, $pos, time(), self::$userID
            ));
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

            if(cmsbridge::dbsuccess()) {
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

            return cmsbridge::dbsuccess();
        }   // end function addTag()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function createDir(string $dir)
        {
            if(CMSBRIDGE_CMS_BC1) {
                \CAT_Helper_Directory::createDirectory($dir,0770,true);
            } elseif(CMSBRIDGE_CMS_BC2) {
                \CAT\Helper\Directory::createDirectory($dir,0770,true);
            } else {
                mkdir($dir,0770);
            }
        }   // end function createDir()
        
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
                return cmsbridge::dbsuccess();
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

            // get article data
            $article_data = self::getArticle($articleID);
            $article_data['linkbase'] = '';
            // in BC2, the linkbase is always the current page
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
                if(!cmsbridge::dbsuccess()) {
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
            $pages = cmsbridge::getPages();

            // get groups for this section
            $groups[self::$pageID] = array();
            $groups[self::$pageID][self::$sectionID] = self::getGroups();

            // get groups from any other journal section
            $sections = self::getImportableSections('journal');
            if(is_array($sections) && count($sections)>0 && isset($sections['journal'])) {
                foreach($sections['journal'] as $sectID => $name) {
                    if($sectID != self::$sectionID) { // skip current
                        $pageID = cmsbridge::getPageForSection(intval($sectID));
                        $groups[$pageID][$sectID] = self::getGroups(intval($sectID));
                    }
                }
            }

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
            if(CMSBRIDGE_CMS_BC1) {
                $allowed = \CAT_Helper_Mime::getAllowedFileSuffixes('image/*');
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
         * @access protected
         * @return
         **/
        protected static function getAssignedTags(int $articleID)
        {
            $tags = array();
            $sql  = sprintf(
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
            );
            $query_tags = cmsbridge::db()->query($sql);

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
         * import is possible from the following modules:
         *     + topics
         *     + news (classic)
         *     + news with images
         *     + journal
         *
         * @access protected
         * @return
         **/
        protected static function getImportableSections(?string $type=null)
        {
            $sections = array();
            $fields = '`section_id`';
            if(CMSBRIDGE_CMS_WBCE) {
                $fields .= ', `namesection` AS `section_name`';
            }
            if(empty($type)) {
                $types = array('journal','topics');
            } else {
                $types = array($type);
            }
# !!!!! TODO: Ueber die vorhandenen Importer ermitteln !!!!!!!!!!!!!!!!!!!!!!!!!
            foreach($types as $module) {
                $stmt = cmsbridge::db()->query(sprintf(
                    "SELECT %s FROM `%ssections`" .
                    " WHERE `module`='%s' AND `section_id`!=%d " .
                    "ORDER BY `section_id` ASC",
                    $fields, cmsbridge::dbprefix(), $module, self::$sectionID
                ));
                if(is_object($stmt) && $stmt->rowCount()>0) {
                    while ($row=$stmt->fetch()) {
                        if(!isset($sections[$module])) {
                            $sections[$module] = array();
                        }
                        $val = $row['section_id'];
                        if(isset($row['section_name'])) {
                            $val .= ' ('.$row['section_name'].')';
                        }
                        $sections[$module][$row['section_id']] = $val;
                    }
                }
            }
            return $sections;
        }   // end function getImportableSections()
        

        /**
         *
         * @access
         * @return
         **/
        protected static function getOrder()
        {
            $settings  = self::getSettings(self::$sectionID);
            $orders    = self::getSortorders();

            return array($orders[$settings['view_order']]['order_by'],$orders[$settings['view_order']]['direction']);
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
                'ARTICLE_DATE',
                'ARTICLE_TIME',
                'ARTICLE_ID',                   // ID of the article
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
                'LAST_MODIFIED_BY',             // text "last modified by"
                'MODI_BY',                      // user that modified the article
                'MODI_DATE',                    // article modification date
                'MODI_TIME',                    // article modification time
                'NEXT_LINK',                    // next link
                'NEXT_PAGE_LINK',               // next page link
                'OF',                           // text "of" ("von")
                'OUT_OF',                       // text "out of" ("von")
                'PAGE_TITLE',                   // page title
                'PARENT_PAGE_TITLE',            // title of the parent page (for articles linked together by tags)
'POST_TAGS',                    // Tags
                'PREVIOUS_LINK',                // prev link
                'PREVIOUS_PAGE_LINK',           // prev page link
                'PUBLISHED_DATE',               // published date
                'PUBLISHED_TIME',               // published time
                'SHORT',                        // alias for CONTENT_SHORT
                'SHOW_READ_MORE',               // wether to show "read more" link
                'TAGS',                         // tags
                'TAG_LINK',                     // link that shows all articles with given tags
                'TAG',                          // single tag
                'TAGCOLOR',                     // background color for tag
                'TAGHOVERCOLOR',                // mouseover background color for tag
                'TEXTCOLOR',                    // text color for tag
                'TEXTHOVERCOLOR',               // text mouseover color for tag
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
                'AT'               => self::t('at'),
                'BACK'             => self::t('Back'),
                'BASE_URL'         => CMSBRIDGE_CMS_URL.'/modules/'.JOURNAL_MODDIR,
                'COMPOSED_BY'      => self::t('Posted by'),
                'IN_GROUP'         => self::t('in group'),
                'MODIFIED'         => self::t('Last modified'),
                'LAST_MODIFIED_BY' => self::t('Last modified by'),
                'NEXT'             => self::t('Next article'),
                'O_CLOCK'          => self::t("o'clock"),
                'ON'               => self::t('on'),
                'PREV'             => self::t('Previous article'),
                'READ_MORE'        => self::t('Read more'),
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
                . "WHERE `%s`=%d",
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
                        if(file_exists(JOURNAL_IMGDIR.'/'.self::$sectionID.'/'.$img['picname'])) {
                            if(file_exists(JOURNAL_IMGDIR.'/'.self::$sectionID.'/'.JOURNAL_THUMBDIR.'/'.$img['picname'])) {
                                return "<img src='".JOURNAL_IMGURL.'/'.self::$sectionID.'/'.JOURNAL_THUMBDIR.'/'.$img['picname']."' alt='".$alt."' />";
                            }
                            if(file_exists(JOURNAL_IMGDIR.'/'.self::$sectionID.'/'.$img['picname'])) {
                                return "<img src='".JOURNAL_IMGURL.'/'.self::$sectionID.'/'.$img['picname']."' alt='".$alt."' />";
                            }
                        }
                    }
                }
            }
        }   // end function getPreviewImage()

        /**
         *
         * @access
         * @return
         **/
        protected static function getQueryExtra()
        {
            $query_extra = array();

            // ----- filter by group? --------------------------------------------------
            if (isset($_GET['g']) and is_numeric($_GET['g'])) {
                $query_extra[] = "group_id = '".$_GET['g']."'";
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
                $query_extra[] = "($date_option >= '$startdate' AND $date_option < '$enddate')";
            }

            // ----- filter by tags? ---------------------------------------------------
            if(isset($_GET['tags']) && strlen($_GET['tags'])) {
                $filter_articles = array();
// TODO!!!
                $tags = self::escapeTags($_GET['tags']);
                $r = cmsbridge::db()->query(sprintf(
                    "SELECT `t2`.`article_id` ".
                    "FROM `%s%s` as `t1` ".
                    "JOIN `%s%s` AS `t2` ON `t1`.`tag_id`=`t2`.`tag_id` ".
                    "JOIN `wbce_mod_journal_articles` AS `t3` ON `t2`.`article_id`=`t3`.`article_id` ".
                    "WHERE `tag` IN ('".implode("', '", $tags)."') ".
                    "GROUP BY `t2`.`article_id`",
                    cmsbridge::dbprefix(), self::$tables['tags'],
                    cmsbridge::dbprefix(), self::$tables['tags_articles']
                ));
                while ($row=$r->fetch()) {
                    $filter_articles[] = $row['article_id'];
                }
                if (count($filter_articles)>0) {
                    $query_extra[] = "`t1`.`article_id` IN (".implode(',', array_values($filter_articles)).") ";
                }
            }

            return implode(' AND ',$query_extra);
        }   // end function getQueryExtra()

        /**
         *
         * @access
         * @return
         **/
        protected static function getSettings() : array
        {
            if(empty(self::$settings)) {
                self::$settings = self::getSettingsForSection(self::$sectionID);
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
        protected static function getSortorders()
        {
            $q = cmsbridge::db()->query(sprintf(
                "SELECT * FROM `%s%s` ORDER BY `order_name` ASC",
                cmsbridge::dbprefix(), self::$tables['sortorder']
            ));
            // get settings from DB
            if(!empty($q) && $q->rowCount()) {
                $rows = $q->fetchAll();
                $result = array();
                foreach($rows as $row) {
                    $result[$row['order_id']] = $row;
                }
                return $result;
            }
        }   // end function getSortorders()

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
                $views = \CAT_Helper_Directory::getDirectories($dir,$dir.'/');
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
         *
         * @access protected
         * @return
         **/
        protected static function Import()
        {
            $source_section = intval($_REQUEST['source_id']);

            // figure out module type
            $stmt = cmsbridge::db()->query(sprintf(
                "SELECT `module` FROM `%ssections` WHERE `section_id`=%d",
                cmsbridge::dbprefix(), $source_section
            ));
            if ($stmt->rowCount()==1) {
                $result = $stmt->fetch();
                $type = $result['module'];
            }

            if(isset($type) && !empty($type)) {
                // check if importer exists
                $classfile = __DIR__.'/Journal/Importer/'.$type.'Import.php';
                if(file_exists($classfile)) {
                    try {
                        include_once $classfile;
                        // call importer
                        $classname = '\CAT\Addon\Journal\Importer\\'.$type.'Import';
                        $classname::import($source_section, self::$sectionID);
                    } catch ( \Exeption $e ) {
echo "ERROR: ", $e->getMessage();
                    }
                }
            }

        }   // end function Import()

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

            // this is for the backend only - article visibility
        	$icon = '';
            $t = time();
            $article['is_visible'] = false;
            if (
                    ($article['published_when']<=$t && $article['published_until']==0)
                || (($article['published_when']<=$t || $article['published_when']==0) && $article['published_until']>=$t)
            ) {
                $article['is_visible'] = true;
            }

            // append group and/or position to the links
            $append_to_links = array();
            if (isset($_GET['p']) and intval($_GET['p']) > 0) { // position
                $append_to_link[] = 'p='.intval($_GET['p']);
            }
            if (isset($_GET['g']) and is_numeric($_GET['g'])) {
                // check if group exists
                $gid = intval($_GET['g']);
                if(isset($group_map[$gid])) {
                    $append_to_link[] = 'g='.$gid;
                }
            }
            $append = null;
            if(count($append_to_links)>0) {
                $append = '?'.implode('&amp;',$append_to_links);
            }

            // article links
            $article['article_link']    = cmsbridge::getPageLink(self::$page['route'].'/'.$article['link']).$append;
            $article['article_path']    = str_ireplace(CMSBRIDGE_CMS_URL, CMSBRIDGE_CMS_PATH, $article['article_link']);
            $article['next_link']       = (isset($article['next_link']) && strlen($article['next_link'])>0 ? cmsbridge::getPageLink(self::$page['route'].'/'.$article['next_link']).$append : null);
            $article['prev_link']       = (isset($article['prev_link']) && strlen($article['prev_link'])>0 ? cmsbridge::getPageLink(self::$page['route'].'/'.$article['prev_link']).$append : null);

            // user (author) info
            $article['display_name']    = isset($users[$article['posted_by']]) ? $users[$article['posted_by']]['display_name'] : '<i>'.$users[0]['display_name'] .'</i>';
            $article['username']        = isset($users[$article['posted_by']]) ? $users[$article['posted_by']]['username'] : '<i>'.$users[0]['username'] .'</i>';
            $article['email']           = isset($users[$article['posted_by']]) ? $users[$article['posted_by']]['email'] : '<i>'.$users[0]['email'] .'</i>';
            $article['modi_by']         = $article['display_name'];
            if(isset($article['modified_by']) && $article['modified_by'] != 0) {
                $article['modi_by']     = isset($users[$article['modified_by']]) ? $users[$article['modified_by']]['display_name'] : $article['display_name'];
            }

            // Get group id, title, and image
            $group_id                   = $article['group_id'];
            $article['group_title']     = ( isset($group_map[$group_id]) ? $group_map[$group_id]['title'] : null );
            $article['group_image']     = ( isset($group_map[$group_id]) ? $group_map[$group_id]['image'] : JOURNAL_NOPIC );
            $article['display_image']   = ($article['group_image'] == '') ? "none" : "inherit";
            $article['display_group']   = ($group_id == 0) ? 'none' : 'inherit';

            // get assigned images
            $article['images'] = self::getImages($article['article_id'],false);

            // find configured preview image
            $prev_img_found = false;
            foreach($article['images'] as $img) {
                if($img['preview']=='Y') {
                    if(file_exists(JOURNAL_IMGDIR.'/'.self::$sectionID.'/'.$img['picname'])) {
                        $article['preview_image'] = "<img src='".JOURNAL_IMGURL.'/'.self::$sectionID.'/'.$img['picname']."' alt='".htmlspecialchars($article['title'], ENT_QUOTES | ENT_HTML401)."' />";
                        $article['preview_image_thumb'] = $article['preview_image'];
                        $article['preview_image_thumb_url'] = JOURNAL_IMGURL.'/'.self::$sectionID.'/'.$img['picname'];
                        if(file_exists(JOURNAL_IMGDIR.'/'.self::$sectionID.'/'.JOURNAL_THUMBDIR.'/'.$img['picname'])) {
                            $article['preview_image_thumb'] = "<img src='".JOURNAL_IMGURL.'/'.self::$sectionID.'/'.JOURNAL_THUMBDIR.'/'.$img['picname']."' alt='".htmlspecialchars($article['title'], ENT_QUOTES | ENT_HTML401)."' />";
                            $article['preview_image_thumb_url'] = JOURNAL_IMGURL.'/'.self::$sectionID.'/'.JOURNAL_THUMBDIR.'/'.$img['picname'];
                        }
                        $prev_img_found = true;
                    }
                }
            }

            // if no preview image was found, use the group image
            if($prev_img_found == false && isset($article['group_image'])) {
                $article['preview_image_thumb'] = "<img src='".$article['group_image']."' alt='".htmlspecialchars($article['title'], ENT_QUOTES | ENT_HTML401)."' />";
                $article['preview_image'] =  "<img src='".$article['group_image']."' alt='".htmlspecialchars($article['title'], ENT_QUOTES | ENT_HTML401)."' />";
            }

            // article dates
            if ($article['published_when'] > 0 && $article['published_when'] > $article['posted_when']) {
                $article['article_date'] = cmsbridge::formatDate($article['published_when']);
                $article['article_time'] = cmsbridge::formatTime($article['published_when']);
            } else {
                $article['article_date'] = cmsbridge::formatDate($article['posted_when']);
                $article['article_time'] = cmsbridge::formatTime($article['posted_when']);
            }

            $article['modi_date'] = (
                empty($article['modified_when'])
                ? $article['article_date']
                : cmsbridge::formatDate($article['modified_when'])
            );
            $article['modi_time'] = (
                empty($article['modified_when'])
                ? $article['article_time']
                : cmsbridge::formatTime($article['modified_when'])
            );

            // get / render the tags
            $article['TAGS'] = self::renderTags(intval($article['article_id']));

            // make sure access file exists
            $dir = WB_PATH.PAGES_DIRECTORY.self::$page['link'].'/';
            $file = $dir.$article['link'].PAGE_EXTENSION;
            self::checkAccessFile(intval($article['article_id']), $file);

            return $article;

        }   // end function processArticle()

        /**
         *
         * @access protected
         * @return
         **/
        protected static function readArticle(int $articleID, array $articles)
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

            // previous / next
            for($i=0; $i<count($articles); $i++) {
                if($articles[$i]['article_id']==$articleID) {
                    if($i>0) {
                        $article['prev_link'] = $articles[$i-1]['article_link'];
                    }
                    if(isset($articles[$i+1])) {
                        $article['next_link'] = $articles[$i+1]['article_link'];
                    }
                }
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
                        JOURNAL_IMGURL.'/'.self::$sectionID.'/'.$img['picname'],
                        $img['picdesc'],
						JOURNAL_IMGURL.'/'.self::$sectionID.'/'.JOURNAL_THUMBDIR.'/'.$img['picname'],
						self::$settings['imgthumbwidth'],
						self::$settings['imgthumbheight'],
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
                // number of images for later use
                $img_count = count($article['images']);
                // no "read more" link if no long content and no images
                if ( (strlen($article['content_long']) < 9) && ($img_count < 1)) {
                    $article['article_link'] = '#" onclick="javascript:void(0);return false;" style="cursor:no-drop;';
                }

                // set replacements for current line
                $replacements = array_merge(
                    $default_replacements,
                    array_change_key_case($article,CASE_UPPER),
                    array(
                        'SHORT'                 => $article['content_short'],
                        'LINK'                  => $article['article_link'],
                        'ARTICLE_DATE'          => $article['article_date'],
                        'ARTICLE_TIME'          => $article['article_time'],
                        'SHOW_READ_MORE'        => (strlen($article['content_long'])<1 && ($img_count<1))
                                                ? 'hidden' : 'visible',
                        'NUMBER'                => $i,
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

            $replacements = $default_replacements;

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
            $tags = self::getAssignedTags($articleID);
            if(is_array($tags) && count($tags)>0) {
                $taghtml = array();
                $pagelink = cmsbridge::getPageLink(self::$page['route'])
                          . '?tags=';
                foreach ($tags as $i => $tag) {
                    foreach(array('tag_color','text_color','hover_color','text_hover_color') as $key) {
                        $d[$key] = ((isset($tag[$key]) && strlen($tag[$key]))
                                 ? $tag[$key] : '');
                    }
                    $taghtml[] = str_replace(
                        array('[TAG_LINK]','[TAGCOLOR]','[TAGHOVERCOLOR]','[TEXTCOLOR]','[TEXTHOVERCOLOR]','[TAGID]','[TAG]','[PAGEID]'),
                        array($pagelink.$tag['tag'],$d['tag_color'],$d['hover_color'],$d['text_color'],$d['text_hover_color'],$i,$tag['tag'],self::$pageID),
                        self::$settings['tag_loop']
                    );
                }
                return self::$settings['tag_header']
                     . implode("\n",$taghtml)
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
         *    3    - no such file
         *
         * @param $src    - image source
         * @param $dst    - save to
         * @param $width  - new width
         * @param $height - new height
         * @param $crop   - 0=no, 1=yes
         **/
        protected static function resize(string $src, string $dst, int $width, int $height, int $crop=0)
        {
            // check data
            if(!file_exists($src)) {
                return 3;
            }
            if(!$width >= 1 || !$height>=1) {
                return false;
            }
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
                    if (!$img) {
                        $img = imagecreatefromstring(file_get_contents($src));
                    }
                } catch (\Exception $e) {
                    return false;
                }
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

            // try to fix orientation
            if(function_exists('exif_read_data')) {
                $exif = exif_read_data($filename);
                if ($exif && isset($exif['Orientation']))
                {
                    $ort = $exif['Orientation'];
                    if ($ort == 6 || $ort == 5)
                        $img = imagerotate($img, 270, null);
                    if ($ort == 3 || $ort == 4)
                        $img = imagerotate($img, 180, null);
                    if ($ort == 8 || $ort == 7)
                        $img = imagerotate($img, 90, null);
                    if ($ort == 5 || $ort == 4 || $ort == 7)
                        imageflip($img, IMG_FLIP_HORIZONTAL);
                }
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
        }   // end function resize

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
                list($section,$group) = explode('_',$_REQUEST['group']);
                if (intval($section)!=0 && intval($group)!=0) {
                    $qb->set($c->quoteIdentifier('group_id'), intval($group));
                    if($section != self::$sectionID) {
                        $qb->set($c->quoteIdentifier('section_id',intval($section)));
                    }
                }
            }

            // ===== modified date and user ====================================
            $qb->set($c->quoteIdentifier('modified_when'), time());
            $qb->set($c->quoteIdentifier('modified_by'), self::$userID);

            // ===== save ======================================================
            $qb->update(sprintf('%s%s',cmsbridge::dbprefix(), self::$tables['articles']))
               ->where('`article_id`='.$articleID);

            $r = $qb->execute();

            if(!cmsbridge::dbsuccess()) {
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
                        $sql = sprintf(
                            "INSERT IGNORE INTO `%s%s` VALUES(%d,%d)",
                            cmsbridge::dbprefix(), self::$tables['tags_articles'],
                            $articleID, $t
                        );
                        cmsbridge::db()->query($sql);
                    }
                }
            }

            // ===== images ====================================================
            // change image data
            $images = self::getImages($articleID);
            if(count($images) > 0) {
                foreach ($images as $row) {
                    $row_id = $row['pic_id'];
                    $picdesc = isset($_POST['picdesc'][$row_id])
                             ? cmsbridge::escapeString(strip_tags($_POST['picdesc'][$row_id]))
                             : '';
                    $preview = (isset($_POST['preview']) && isset($_POST['preview'][$row_id]))
                             ? 'Y'
                             : 'N';
                    cmsbridge::db()->query(sprintf(
                        "UPDATE `%s%s` SET `picdesc`='%s' WHERE `pic_id`=%d",
                        cmsbridge::dbprefix(), self::$tables['images'], $picdesc, $row_id
                    ));
                    cmsbridge::db()->query(sprintf(
                        "UPDATE `%s%s` SET `preview`='%s' WHERE `article_id`=%d AND `pic_id`=%d",
                        cmsbridge::dbprefix(), self::$tables['articles_img'], $preview, $articleID, $row_id
                    ));
                }
            }

            // BC1 / WBCE: create access file
            if(!CMSBRIDGE_CMS_BC2) {
# !!!!! TODO: After move the page is no longer self::$page !!!!!!!!!!!!!!!!!!!!!
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
                'imgthumbwidth'         => 'int',
                'imgthumbheight'        => 'int',
                'view'                  => 'string',
                'append_title'          => 'string',
                'image_subdir'          => 'string',
            );

            // make sure current settings are loaded
            $settings_db = self::getSettings();
            $mode = self::$settings['mode'];
            $new_gallery = $new_view = false;

            // create a query builder instance to collect the data
            $qb = cmsbridge::conn()->createQueryBuilder();
            $c  = $qb->getConnection();

            if($settings_db && !isset($settings_db['___defaults___'])) {
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
                    $qb->$func($c->quoteIdentifier($var), ($type=='string' ? $c->quote($_REQUEST[$var]) : intval($_REQUEST[$var])));
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
                                    $qb->$func($c->quoteIdentifier('header'),$c->quote($header));
                                    $qb->$func($c->quoteIdentifier('article_loop'),$c->quote($article_loop));
                                    $qb->$func($c->quoteIdentifier('footer'),$c->quote($footer));
                                    $qb->$func($c->quoteIdentifier('article_header'),$c->quote($article_header));
                                    $qb->$func($c->quoteIdentifier('article_content'),$c->quote($article_content));
                                    $qb->$func($c->quoteIdentifier('article_footer'),$c->quote($article_footer));
                                    $new_view = true;
                                }
                            }
                        }

                        $val = null;
                        switch($var) {
                            case 'imgmaxsize':
                                $val = intval($_REQUEST[$var]) * 1024;
                                break;
                            case 'image_loop':
                                // ignore if gallery has changed
                                if(!$new_gallery) {
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

            if(!cmsbridge::dbsuccess()) {
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