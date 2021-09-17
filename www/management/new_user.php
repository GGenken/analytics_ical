<?php include_once $_SERVER['DOCUMENT_ROOT'].'/Calendar/ROOT.php';

include_once ROOT.'/classes/User.php';

/*
 * Заносит токен Аналитики в БД
 */

if (!isset($_REQUEST['analytics_token'])) { RAISE('Analytics token not specified'); }

$User = @new User(
	(string)$_REQUEST['analytics_token'],
	$type = 'analytics_token',
	$build_ical = false
) or RAISE('Unknown error');

if ($User->init_new_user()) {
	echo json_encode(['status' => 'success', 'registered_token' => $User->get_analytics_token()]);
} else {
	RAISE('Failed to register Analytics token');
}