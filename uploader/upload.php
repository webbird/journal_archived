<?php


header('Content-type:application/json;charset=utf-8');

require_once('../../../config.php');
// check if user has permissions to access the journal module
require_once(WB_PATH.'/framework/class.admin.php');
$admin = new admin('Pages', 'pages_modify', false, false);
if (!($admin->is_authenticated() && $admin->get_permission('journal', 'module'))) {
    throw new RuntimeException('insuficcient rights');
}

if(!isset($_GET['article_id'])){
    throw new RuntimeException('missing parameters');
}

$article_id = $admin->checkIDKEY('article_id', false, 'GET', true);
if(defined('WB_VERSION') && (version_compare(WB_VERSION, '2.8.3', '>'))) 
    $article_id = intval($_GET['article_id']);
if(! is_numeric($article_id) || (intval($article_id)<=0)){
    throw new RuntimeException('wrong parameter value');
}

require_once __DIR__.'/../functions.inc.php';

// get section id
$section_id = mod_journal_section_by_postid(intval($article_id));

// fetch settings
$settings = mod_journal_settings_get($section_id);

$settings['imgmaxsize'] = intval($settings['imgmaxsize']);
$iniset = ini_get('upload_max_filesize');
$iniset = mod_journal_return_bytes($iniset);

list($previewwidth,$previewheight,$thumbwidth,$thumbheight) = mod_journal_get_sizes($section_id);

$imageErrorMessage = '';
$imagemaxsize  = ($settings['imgmaxsize']>0 && $settings['imgmaxsize'] < $iniset)
    ? $settings['imgmaxsize']
    : $iniset;

$imagemaxwidth  = $settings['imgmaxwidth'];
$imagemaxheight = $settings['imgmaxheight'];
$crop           = ($settings['crop_preview'] == 'Y') ? 1 : 0;

