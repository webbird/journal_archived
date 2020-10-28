<?php

header('Content-type:application/json;charset=utf-8');

// ===== check input ===========================================================
if(!isset($_GET['article_id']) || !isset($_GET['section_id'])) {
    echo json_encode(array(
        'status' => 'error',
        'message' => 'missing parameters'
    ));
    exit;
}

$article_id = intval($_GET['article_id']);
if(empty($article_id)) {
    echo json_encode(array(
        'status' => 'error',
        'message' => 'invalid parameters'
    ));
    exit;
}
$section_id = intval($_GET['section_id']);
if(empty($section_id)) {
    echo json_encode(array(
        'status'  => 'error',
        'message' => 'invalid parameters'
    ));
    exit;
}

// ===== figure out cms ========================================================
require_once __DIR__.'/../inc/class.cmsbridge.php';
$cms = \CAT\Addon\cmsbridge::identify();

switch($cms) {
    case 'WBCE':
        require_once __DIR__.'/../../../config.php';
        // check permissions
        require_once WB_PATH.'/framework/class.admin.php';
        $admin = new admin('Pages', 'pages_modify', false, false);
        if (!($admin->is_authenticated() && $admin->get_permission('journal', 'module'))) {
            echo json_encode(array(
                'status'  => 'error',
                'message' => 'access denied'
            ));
            exit;
        }
        if(method_exists($database,'get_array')) {
            $section = $database->get_array(sprintf(
                'SELECT * FROM `%ssections` WHERE `section_id`= %d',
                TABLE_PREFIX, intval($section_id)
            ));
            $section = $section[0];
        }
        break;
    case 'BC2':
        require_once __DIR__.'/../../../CAT/bootstrap.php';
        $section = \CAT\Sections::getSection($section_id,1);
        break;
}

// ===== initialize ============================================================
require_once __DIR__.'/../inc/class.journal.php';
\CAT\Addon\journal::initialize($section);

$result   = \CAT\Addon\journal::handleUpload($article_id);

echo json_encode($result);
exit;