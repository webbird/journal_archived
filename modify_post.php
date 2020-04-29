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
    $section = $wb->get_section_details($section_id);
    \CAT\Addon\journal::initialize($section);
    echo \CAT\Addon\journal::modify($section);
} else {
    echo "oh no!";
    exit;
}