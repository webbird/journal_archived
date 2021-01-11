<?php

header('Content-type:application/json;charset=utf-8');

function journal_getArticleSection(int $articleID)
{
    $stmt = \CAT\Addon\cmsbridge::db()->query(sprintf(
        'SELECT `section_id` FROM `%smod_journal_articles` WHERE `article_id`=%d',
        \CAT\Addon\cmsbridge::dbprefix(), $articleID
    ));
    if($stmt->rowCount()>0) {
        $row = $stmt->fetch();
        return intval($row['section_id']);
    }
    return 0;
}

// ===== check input ===========================================================
if(!isset($_REQUEST['article_id'])) {
    echo json_encode(array(
        'status' => 'error',
        'message' => 'missing parameters'
    ));
    exit;
}

$articleID = intval($_REQUEST['article_id']);
if(empty($articleID)) {
    echo json_encode(array(
        'status' => 'error',
        'message' => 'invalid parameters'
    ));
    exit;
}

// check permissions
if (file_exists(__DIR__.'/../../../framework/Insert.php')) {
    require_once __DIR__.'/../../../config.php';
    require_once WB_PATH.'/framework/class.admin.php';
    $admin = new admin('Pages', 'pages_modify', false, false);
    if (!($admin->is_authenticated() && $admin->get_permission('journal', 'module'))) {
        echo json_encode(array(
            'status'  => 'error',
            'message' => 'access denied'
        ));
        exit;
    }
} elseif(file_exists(__DIR__.'/../../../CAT/Hook.php')) {
    require_once __DIR__.'/../../../CAT/bootstrap.php';
# !!!!! TODO: check permissions !!!!!
    $section = \CAT\Sections::getSection($section_id,1);
} elseif(file_exists(__DIR__.'/../../../framework/CAT/Object.php')) {
    require_once __DIR__.'/../../../config.php';
    if(!\CAT_Users::is_authenticated()) {
        echo json_encode(array(
            'status'  => 'error',
            'message' => 'access denied'
        ));
        exit;
    }
} else { // unknown CMS
    echo json_encode(array(
        'status'  => 'error',
        'message' => 'access denied'
    ));
    exit;
}

require_once __DIR__.'/../inc/class.cmsbridge.php';
require_once __DIR__.'/../inc/class.journal.php';

// get section id
$section_id = journal_getArticleSection($articleID);
\CAT\Addon\cmsbridge::initialize();


$section = \CAT\Addon\cmsbridge::getSection($section_id);

\CAT\Addon\journal::initialize($section);

$result   = \CAT\Addon\journal::handleUpload($articleID);

echo json_encode($result);
exit;