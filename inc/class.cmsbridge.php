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
   @package         cmsbridge

   Note: This is a hybrid module that is indented to run with WBCE,
   BlackCat CMS v1.x and BlackCat CMS v2.x. There are some concessions to
   make this work.

*/

namespace CAT\Addon;

spl_autoload_register(function ($class) {
    $file = str_replace('\\', '/', $class);
    $parts = explode('/', $file);
    #echo "autoload $file (class $class)<br />";
    if (isset($parts[2]) && in_array($parts[2], array('journal','cmsbridge'))) {
        $file = str_ireplace('CAT/Addon/', '', $file);
        if (file_exists(__DIR__.'/'.$file.'.php')) {
            require __DIR__.'/'.$file.'.php';
        } elseif (file_exists(__DIR__.'/class.'.$file.'.php')) {
            require __DIR__.'/class.'.$file.'.php';
        }
    }
    // next in stack
});

/*******************************************************************************
 * compatibility functions | "CMS abstraction"
 ******************************************************************************/
if (!class_exists('cmsbridge', false))
{
    final class cmsbridge
    {
        /**
         * @var name of the language var in the lang files;
         *      see method setLangVar()
         **/
        protected static $langvar     = 'LANG';
        /**
         * @var database handler
         **/
        protected static $db          = null;
        /**
         * @var table prefix
         **/
        protected static $prefix      = null;
        /**
         * @var abstraction for WBCE
         **/
        protected static $admin       = null;
        /**
         * @var
         **/
        protected static $routemap    = array();
        /**
         * @var
         **/
        protected static $routeparms  = array();

        /**
         * BC2 automagically calls initialize(), but BC1 and WBCE don't
         *
         * we use this for hybrid modules to distinguish the CMS variants and
         * define some constants for later use
         **/
        public static function initialize(?array $section=array())
        {
            if (!defined('CMSBRIDGE_CMS_NAME')) {
                define('CMSBRIDGE_CMS_WB'   , ((defined('WB_VERSION')    && !defined('CAT_VERSION') && !defined('WBCE_VERSION')) ? true : false));
                define('CMSBRIDGE_CMS_WBCE' , ((defined('WBCE_VERSION')) ? true    : false));
                define('CMSBRIDGE_CMS_BC1'  , ((defined('CAT_VERSION')   && version_compare(CAT_VERSION, '2.0', '<'))  ? true : false));
                define('CMSBRIDGE_CMS_BC2'  , ((defined('CAT_VERSION')   && version_compare(CAT_VERSION, '2.0', '>=')) ? true : false));
                define('CMSBRIDGE_CMS_URL'  , (defined('WB_URL')         ? WB_URL    : (CMSBRIDGE_CMS_BC2 ? CAT_SITE_URL.'/'  : CAT_URL)));
                define('CMSBRIDGE_ADMIN_URL', (defined('WB_URL')      ? ADMIN_URL : CAT_ADMIN_URL));
                define('CMSBRIDGE_CMS_NAME' , (CMSBRIDGE_CMS_WBCE        ? 'wbce'    : (CMSBRIDGE_CMS_WB ? 'wb' : 'bc')));

                $basepath = (defined('WB_PATH') ? WB_PATH : (CMSBRIDGE_CMS_BC2 ? CAT_ENGINE_PATH : CAT_PATH));
                if (CMSBRIDGE_CMS_BC2) {
                    $basepath = \CAT\Helper\Directory::sanitizePath($basepath);
                }
                if (CMSBRIDGE_CMS_BC1) {
                    $basepath = \CAT_Helper_Directory::sanitizePath($basepath);
                }

                define('CMSBRIDGE_MEDIA', (CMSBRIDGE_CMS_BC2 ? \CAT\Base::getSetting('media_directory') : MEDIA_DIRECTORY));
                define('CMSBRIDGE_CMS_PATH', $basepath);
            }

            $s = null;

            if (isset($section['section_id'])) {
                $s = intval($section['section_id']);
                define('CMSBRIDGE_SECTION', $s);
                define('CMSBRIDGE_PAGE', self::pagefor($s));
                if(isset($section['module'])) {
                    define('CMSBRIDGE_MODULE', $section['module']);
                }
            }

            if (CMSBRIDGE_CMS_BC2) {
                if (!defined('CMSBRIDGE_MEDIA_FULLDIR')) {
                    define('CMSBRIDGE_MEDIA_FULLDIR', CAT_PATH.'/'.CMSBRIDGE_MEDIA);
                }
                if (!empty($s)) {
                    \CAT\Addon\Module::initialize($section);
                    $base = '/'.CAT_MODULES_FOLDER.'/'.$section['module'];
                    if (\CAT\Backend::isBackend()) {
                        if (!file_exists(CAT_ENGINE_PATH.'/'.$base.'/headers.inc.php')) {
                            file_exists(CAT_ENGINE_PATH.'/'.$base.'/backend.css')
                            && \CAT\Helper\Assets::addCSS($base.'/backend.css');
                            file_exists(CAT_ENGINE_PATH.'/'.$base.'/css/backend.css')
                            && \CAT\Helper\Assets::addCSS($base.'/css/backend.css');
                            file_exists(CAT_ENGINE_PATH.'/'.$base.'/backend.js')
                            && \CAT\Helper\Assets::addJS($base.'/backend.js', 'header');
                            file_exists(CAT_ENGINE_PATH.'/'.$base.'/js/backend.js')
                            && \CAT\Helper\Assets::addJS($base.'/js/backend.js', 'header');
                        }
                        if (!file_exists(CAT_ENGINE_PATH.'/'.$base.'/footers.inc.php')) {
                            file_exists(CAT_ENGINE_PATH.'/'.$base.'/backend_body.js')
                            && \CAT\Helper\Assets::addJS($base.'/backend_body.js', 'footer');
                            file_exists(CAT_ENGINE_PATH.'/'.$base.'/js/backend_body.js')
                            && \CAT\Helper\Assets::addJS($base.'/js/backend_body.js', 'footer');
                        }
                    }
                }
            }

            if (CMSBRIDGE_CMS_WBCE && !defined('CMSBRIDGE_MEDIA_FULLDIR')) {
                define('CMSBRIDGE_MEDIA_FULLDIR', WB_PATH.'/'.CMSBRIDGE_MEDIA);
            }

            if (CMSBRIDGE_CMS_BC1 && !defined('CMSBRIDGE_MEDIA_FULLDIR')) {
                define('CMSBRIDGE_MEDIA_FULLDIR', CAT_PATH.'/'.CMSBRIDGE_MEDIA);
            }

            // BC2 routes to URLs in BC1 and WBCE
            self::addRoute('/pages/edit/{id}', '/pages/modify.php?page_id={id}');
            self::addRoute('/pages/save/{id}', '/pages/save.php?page_id={id}');
            
            self::$admin = new \CAT\Addon\admin();
        }   // end function initialize()

        /**
         *
         * @access public
         * @return
         **/
        public static function addRoute(string $route, string $url, ?array $params=array())
        {
            self::$routemap[$route] = $url;
            self::$routeparms[$route] = $params;
        }   // end function addRoute()

        /**
         *
         * @access public
         * @return
         **/
        public static function formatDate(string $date, ?bool $long=false)
        {
            if (CMSBRIDGE_CMS_BC2) {
                return \CAT\Helper\DateTime::getDate($date, $long);
            } elseif (CMSBRIDGE_CMS_BC1) {
                return \CAT_Helper_DateTime::getDate($date, $long);
            } elseif (CMSBRIDGE_CMS_WBCE || CMSBRIDGE_CMS_WB) {
                $returndate = gmdate(DATE_FORMAT, $date+TIMEZONE);
                if ($long) {
                    $returndate = $returndate . ' ' . gmdate(TIME_FORMAT, $date+TIMEZONE);
                }
                return $returndate;
            } else {
                return $date;
            }
        }   // end function formatDate()

        /**
         *
         * @access public
         * @return
         **/
        public static function formatTime(string $time)
        {
            if (CMSBRIDGE_CMS_BC2) {
                return \CAT\Helper\DateTime::getTime($time);
            } elseif (CMSBRIDGE_CMS_BC1) {
                return \CAT_Helper_DateTime::getTime($time);
            } elseif (CMSBRIDGE_CMS_WBCE || CMSBRIDGE_CMS_WB) {
                return gmdate(TIME_FORMAT, $time+TIMEZONE);
            } else {
                return $time;
            }
        }   // end function formatTime()

        /**
         * as of WBCE, some modules use $LANG as var, too
         * this may lead to wrong translations
         * so we allow to define a different lang var here
         * example: JOURNAL_LANG
         **/
        /**
         *
         * @access public
         * @return
         **/
        public static function setLangVar(string $name)
        {
            self::$langvar = $name;
        }   // end function setLangVar()

        public static function escapeString($string)
        {
            if (empty($string)) {
                return '';
            }
            global $database;
            if (is_object($database) && method_exists($database, 'escapeString')) {
                return $database->escapeString($string);
            } else {
                if (defined('CAT_PATH')) {
                    $quoted = self::db()->conn()->quote($string);
                    $quoted = substr_replace($quoted, '', 0, 1);
                    $quoted = substr_replace($quoted, '', -1, 1);
                    return $quoted;
                }
            }
        }

        /**
         *
         * @access public
         * @return
         **/
        public static function getAdminPath()
        {
            $adminpath    = '';
            if (CMSBRIDGE_CMS_BC2 && \CAT\Backend::isBackend()) {
                $adminpath = '/'.CAT_BACKEND_PATH;
            } elseif (CMSBRIDGE_CMS_BC1 && \CAT_Backend::isBackend()) {
                $adminpath = '/'.CAT_BACKEND_PATH;
            } elseif (CMSBRIDGE_CMS_WBCE && defined('ADMIN_DIRECTORY')) {
                #if (!substr_count(CMSBRIDGE_ADMIN_URL, ADMIN_DIRECTORY)) {
                    $adminpath = '/'.ADMIN_DIRECTORY;
                #}
            }
            return $adminpath;
        }   // end function getAdminPath()
        
        /**
         *
         * @access public
         * @return
         **/
        public static function getFTAN()
        {
            $code = self::$admin->getFTAN();
            // while WBCE returns the full HTML, BC1 does not
            if (substr_compare($code, '<input ', 0, strlen('<input ')) != 0) {
                $code = '<input type="hidden" name="ftan_token" value="'.htmlspecialchars($code).'" />';
            }
            return $code;
        }   // end function getFTAN()
        
        /**
         * this is only needed as we can't inherit from \CAT\Addons here
         *
         * returns the module info (name, version, ...)
         **/
        public static function getInfo(string $value=null) : array
        {
            if ($value) {
                return static::$$value;
            }
            // get 'em all
            $info = array();
            foreach (array_values(array(
                'name', 'directory', 'version', 'author', 'license', 'description', 'guid', 'home', 'platform', 'type'
            )) as $key) {
                if (isset(static::$$key) && strlen(static::$$key)) {
                    $info[$key] = static::$$key;
                }
            }
            return $info;
        }   // end function getInfo()

        /**
         *
         * @access public
         * @return
         **/
        public static function getLastInsertId()
        {
            if (method_exists(self::db(), 'getLastInsertId')) {
                $ret = self::db()->getLastInsertId();
            } else {
                $ret = self::conn()->lastInsertId();
            }
            return $ret;
        }   // end function getLastInsertId()

        /**
         *
         * @access public
         * @return
         **/
        public static function getPage(int $pageID)
        {
            if (CMSBRIDGE_CMS_WBCE || CMSBRIDGE_CMS_WB) {
                // Get page info
                $query_page = self::db()->query(sprintf(
                    "SELECT * FROM `%spages` ".
                    "WHERE `page_id`=%d",
                    self::dbprefix(),
                    $pageID
                ));
                if ($query_page->rowCount() > 0) {
                    return $query_page->fetch();
                }
            }
            if (CMSBRIDGE_CMS_BC2) {
                return \CAT\Helper\Page::properties($pageID);
            }
            if (CMSBRIDGE_CMS_BC1) {
                return \CAT_Helper_Page::getPage($pageID);
            }
        }   // end function getPage()
        
        /**
         *
         * @access public
         * @return
         **/
        public static function getPages()
        {
            if (CMSBRIDGE_CMS_WBCE || CMSBRIDGE_CMS_WB) {
                $q = self::db()->query(sprintf(
                    'SELECT `page_id`, `menu_title` FROM `%spages` WHERE `visibility` IN ("public","hidden","none")',
                    self::dbprefix()
                ));
                if (!empty($q) && $q->rowCount()) {
                    $data = $q->fetchAll();
                    $pages = array();
                    foreach ($data as $i => $item) {
                        $pages[$item['page_id']] = $item['menu_title'];
                    }
                    return $pages;
                }
            }
            if (CMSBRIDGE_CMS_BC2) {
                return \CAT\Helper\Page::getPages();
            }
            if (CMSBRIDGE_CMS_BC1) {
                return \CAT_Helper_Page::getPages();
            }
        }   // end function getPage()

        /**
         *
         * @access public
         * @return
         **/
        public static function getPageForSection(int $sectionID) : int
        {
            if (CMSBRIDGE_CMS_BC2) {
                return \CAT\Sections::getPageForSection($sectionID);
            }
            if (CMSBRIDGE_CMS_WBCE) {
                global $wb;
                $section = $wb->get_section_details($sectionID);
                return (isset($section['page_id']) ? $section['page_id'] : 0);
            }
            return 0;
        }   // end function getPageForSection()

        /**
         *
         * @access public
         * @return
         **/
        public static function getPageLink(string $link) : string
        {
            global $wb;
            if (CMSBRIDGE_CMS_WBCE || CMSBRIDGE_CMS_WB) {
                if (!is_object($wb)) {
                    require_once WB_PATH.'/framework/class.wb.php';
                    $wb = new \wb();
                }
                return $wb->page_link($link);
            } elseif(CMSBRIDGE_CMS_BC1) {
                return \CAT_Helper_Page::getLink($link);
            } else {
                return \CAT\Helper\Page::getLink($link);
            }
        }   // end function getPageLink()
        
        /**
         *
         * @access public
         * @return
         **/
        public static function getRoute(string $route, ?array $repl=array())
        {
            if (isset(self::$routemap[$route])) {
                $placeholders = array_merge(array('{id}','{s}'), self::$routeparms[$route]);
                $replacements = array_merge(array(CMSBRIDGE_PAGE,CMSBRIDGE_SECTION), $repl);
                $adminpath    = self::getAdminPath();
                $return       = $adminpath . str_replace(
                    $placeholders,
                    $replacements,
                    (CMSBRIDGE_CMS_BC2 ? $route : self::$routemap[$route])
                );
                return $return;
            }
            return $route;
        }   // end function getRoute()

        /**
         *
         * @access public
         * @return
         **/
        public static function getSection(int $section_id)
        {
            global $wb, $admin;
            // WBCE
            if (is_object($wb)) {
                $section = $wb->get_section_details($section_id);
            // WB
            } elseif (is_object($admin)) {
                $q = self::db()->query(sprintf(
                    'SELECT * FROM `%ssections` WHERE `section_id` = %d',
                    self::dbprefix(),
                    $section_id
                ));
                $section = $q->fetch();
            } elseif (defined('CAT_VERSION')) {
                if (version_compare(CAT_VERSION, '2.0', '<')) {
                    $section = \CAT_Sections::getSection($section_id);
                } else {
                    $section = \CAT\Sections::getSection($section_id,true);
                }
            } else {
                echo "oh no!";
                exit;
            }
            return $section;
        }   // end function getSection()

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
         * identify CMS without having to load anything
         *
         * @access public
         * @return
         **/
        public static function identify()
        {
            // WBCE
            if (file_exists(__DIR__.'/../../../framework/Insert.php')) {
                return "WBCE";
            }
            // BC2
            if (file_exists(__DIR__.'/../../../CAT/Hook.php')) {
                return "BC2";
            }
            if (file_exists(__DIR__.'/../../../framework/CAT/Object.php')) {
                return "BC1";
            }
        }   // end function identify()

        /**
         *
         * @access public
         * @return
         **/
        public static function isAjaxRequest()
        {
            if (CMSBRIDGE_CMS_BC2) {
                return \CAT\Base::asJSON();
            }
        }   // end function isAjaxRequest()

        /**
         *
         * @access public
         * @return
         **/
        public static function pagefor(int $sectionID)
        {
            if (CMSBRIDGE_CMS_BC2) {
                return \CAT\Sections::getPageForSection($sectionID);
            }
            if (CMSBRIDGE_CMS_WBCE || CMSBRIDGE_CMS_WB || CMSBRIDGE_CMS_BC1) {
                $stmt = self::db()->query(sprintf(
                    'SELECT `page_id` FROM `%ssections` WHERE `section_id`=%d',
                    self::dbprefix(),
                    $sectionID
                ));
                if (!empty($stmt) && $stmt->rowCount()) {
                    $pg = $stmt->fetch();
                    return $pg['page_id'];
                }
            }
        }   // end function pagefor()

        /**
         * I18n
         *
         * @access public
         * @param  string  message to 'translate'
         * @return string
         **/
        public static function t(string $message)
        {
            if (CMSBRIDGE_CMS_BC2) {
                return \CAT\Base::lang()->translate($message);
            }
            if (!isset(${self::$langvar})) {
                if (!defined('LANGUAGE')) {
                    define('LANGUAGE', 'EN');
                }
                $langfile = CMSBRIDGE_CMS_PATH.'/modules/'.CMSBRIDGE_MODULE.'/languages/'.LANGUAGE.'.php';
                if (file_exists($langfile)) {
                    require $langfile;
                }
            }
            $v = ${self::$langvar};
            if (isset($v[$message])) {
                return $v[$message];
            }
            return $message;
        }   // end function t()

        /**
         *
         * @access public
         * @return
         **/
        public static function wysiwyg($id, $content, $width, $height)
        {
            if (CMSBRIDGE_CMS_BC2) {
                return \CAT\Helper\WYSIWYG::editor()->showEditor($id, $content, $width, $height);
            }
            if (CMSBRIDGE_CMS_BC1) {
                if (!function_exists('show_wysiwyg_editor')) {
                    if (file_exists(CMSBRIDGE_CMS_PATH.'/modules/ckeditor4/include.php')) {
                        include_once CMSBRIDGE_CMS_PATH.'/modules/ckeditor4/include.php';
                    }
                }
                if (function_exists('show_wysiwyg_editor')) {
                    return show_wysiwyg_editor($id, $id, $content, $width, $height, false);
                }
            }
            if (CMSBRIDGE_CMS_WBCE) {
                if (!function_exists('show_wysiwyg_editor')) {
                    if (file_exists(CMSBRIDGE_CMS_PATH.'/modules/ckeditor/include.php')) {
                        include_once CMSBRIDGE_CMS_PATH.'/modules/ckeditor/include.php';
                    }
                    if (!function_exists('show_wysiwyg_editor')) {
                        return "<textarea name=\"$id\" id=\"$id\" style=\"width:$width;height:$height\">$content</textarea><br />\n".
                             "<span style=\"color:#c00;font-size:smaller\">".
                             self::t('Please note: There is no WYSIWYG Editor installed').
                             "</span>\n";
                    }
                }
                if (function_exists('show_wysiwyg_editor')) {
                    return show_wysiwyg_editor($id, $id, $content, $width, $height);
                }
            }
        }   // end function wysiwyg()
        

        /**
         *
         * @access protected
         * @return
         **/
        public static function admin()
        {
            if (!is_object(self::$admin)) {
                self::$admin = new admin();
            }
            return self::$admin;
        }   // end function admin()

        /**
         *
         * @access public
         * @return
         **/
        public static function conn()
        {
            if (CMSBRIDGE_CMS_BC1 === true || CMSBRIDGE_CMS_BC2 === true) {
                return self::db()->conn();
            }
            if (CMSBRIDGE_CMS_WBCE === true) {
                return self::db()->DbHandle;
            }
            if (CMSBRIDGE_CMS_WB === true) {
                return self::db();
            }
        }   // end function conn()

        /**
         *
         * @access public
         * @return
         **/
        public static function db()
        {
            global $database;
            if (!is_object(self::$db)) {
                self::$prefix = defined('CAT_TABLE_PREFIX') ? CAT_TABLE_PREFIX
                              : (defined('TABLE_PREFIX')   ? TABLE_PREFIX
                              : '');
                if (defined('CMSBRIDGE_CMS_BC2') && CMSBRIDGE_CMS_BC2===true) {
                    // we have a ready-to-use Doctrine object
                    self::$db = \CAT\Base::db();
                } elseif (defined('CMSBRIDGE_CMS_BC1') && CMSBRIDGE_CMS_BC1===true) {
                    // we have a ready-to-use Doctrine object
                    self::$db = $database;
                } elseif(file_exists(__DIR__.'/../../../framework/class.database.php')) {
                    require_once __DIR__.'/../../../framework/class.database.php';
                    self::$db = new \database();
                    self::$prefix = defined('CAT_TABLE_PREFIX') ? CAT_TABLE_PREFIX
                              : (defined('TABLE_PREFIX')   ? TABLE_PREFIX
                              : '');
                } else {
                    if (is_object($database) && is_object($database->db_handle) && $database->db_handle instanceof \Doctrine\DBAL\Connection) {
                        self::$db = $database;
                    } else {
                        // old WBCE and WB only
                        $config = new \Doctrine\DBAL\Configuration();
                        $config->setSQLLogger(new \Doctrine\DBAL\Logging\DebugStack());
                        if (!class_exists('\Doctrine\Common\EventManager')) {
                            require __DIR__.'/../vendor/doctrine/event-manager/lib/Doctrine/Common/EventManager.php';
                        }
                        $evtmgr = new \Doctrine\Common\EventManager();
                        $connectionParams = array(
                            'charset'  => 'utf8',
                            'driver'   => 'pdo_mysql',
                            'dbname'   => (isset($opt['DB_NAME'])     ? $opt['DB_NAME']     : (defined('DB_NAME')     ? DB_NAME : null)),
                            'host'     => (isset($opt['DB_HOST'])     ? $opt['DB_HOST']     : (defined('DB_HOST')     ? DB_HOST : null)),
                            'password' => (isset($opt['DB_PASSWORD']) ? $opt['DB_PASSWORD'] : (defined('DB_PASSWORD') ? DB_PASSWORD : null)),
                            'user'     => (isset($opt['DB_USERNAME']) ? $opt['DB_USERNAME'] : (defined('DB_USERNAME') ? DB_USERNAME : null)),
                            'port'     => (isset($opt['DB_PORT'])     ? $opt['DB_PORT']     : (defined('DB_PORT')     ? DB_PORT : 3306)),
                        );
                        self::$db = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config, $evtmgr);
                    }
                }
            }
            return self::$db;
        }   // end function db()

        /**
         *
         * @access public
         * @return
         **/
        public static function dbsuccess() : bool
        {
            // only older Doctrine versions
            $class = self::conn();
            if(method_exists($class, 'errorCode')) {
                if (empty($class->errorCode()) || $class->errorCode() == '00000') {
                    return true;
                } else {
                    return false;
                }
            }
            // newer Doctrine versions throw exception
            return true;
        }   // end function dbsuccess()

        /**
         *
         * @access public
         * @return
         **/
        public static function dbprefix()
        {
            return self::$prefix;
        }   // end function dbprefix()
    }

    /**
     * WBCE only
     **/
    final class admin
    {
        public function getIDKEY($value)
        {
            global $admin;
            if (is_object($admin) && method_exists($admin, 'getIDKEY')) {
                return $admin->getIDKEY($value);
            }
            return $value;
        }

        public function getFTAN(bool $as_tag=true)
        {
            global $admin;
            if (is_object($admin) && method_exists($admin, 'getFTAN')) {
                return $admin->getFTAN($as_tag);
            }
            return false;
        }

        /**
         *
         * @access public
         * @return
         **/
        public function getUserID()
        {
            global $admin;
            if (is_object($admin) && method_exists($admin, 'get_user_id')) {
                return $admin->get_user_id();
            }
            return false;
        }   // end function getUserID()
    }
}
