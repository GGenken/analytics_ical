<?php include_once $_SERVER['DOCUMENT_ROOT'].'/Calendar/ROOT.php';

include_once ROOT.'/classes/User.php';

/*
 * Удаляет указанный токен
 */

if (!isset($_REQUEST['analytics_token'])) { RAISE('Analytics token not specified'); }
if (!isset($_REQUEST['token'])) { RAISE('Token for deletion not specified'); }

$Target = @new User(
	(string)$_REQUEST['analytics_token'],
	$type = 'analytics_token',
	$build_ical = false
) or RAISE('Unknown error');

echo json_encode(['status' => 'success', 'deleted_token' => $Target->delete_token((string)$_REQUEST['token'])]);