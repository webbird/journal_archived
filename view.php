<?php

/**
   @author          BlackBird Webprogrammierung
   @copyright       2020 BlackBird Webprogrammierung
   @link            https://www.webbird.de
   @license         http://www.gnu.org/licenses/gpl.html
   @package         journal

   Note: This is a crossover module that is indented to run with WBCE,
   BlackCat CMS v1.x and BlackCat CMS v2.x. There are some concessions to
   make this work.

**/

if(!defined('WB_PATH') && !defined('CAT_PATH')) { exit("Cannot access this file directly"); }

require_once __DIR__.'/inc/class.journal.php';

// WBCE
if(is_object($wb)) {
    if(method_exists($database,'get_array')) {
        $section = $database->get_array(sprintf(
            'SELECT * FROM `%ssections` WHERE `section_id`= %d',
            TABLE_PREFIX, intval($section_id)
        ));
        $section = $section[0];
    }
    //$section = $stmt->fetchAssoc();
echo "FILE [",__FILE__,"] FUNC [",__FUNCTION__,"] LINE [",__LINE__,"]<br /><textarea style=\"width:100%;height:200px;color:#000;background-color:#fff;\">";
print_r($section);
echo "</textarea><br />";
    \CAT\Addon\journal::initialize($section);
    echo \CAT\Addon\journal::view($section);
} else {
    echo "oh no!";
    exit;
}