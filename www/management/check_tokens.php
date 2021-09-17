<?php include_once $_SERVER['DOCUMENT_ROOT'].'/Calendar/ROOT.php';

include_once ROOT.'/classes/User.php';

include_once ROOT.'/functions/raise.php';

/*
 * Возвращает все токены пользователя,
 * ассоциированные с токеном в Аналитике
 */

if (!isset($_REQUEST['analytics_token'])) { RAISE('Analytics token not specified'); }

$Target = @new User(
	(string)$_REQUEST['analytics_token'],
	$type = 'analytics_token',
	$build_ical = false
) or RAISE('Unknown error');

echo json_encode([
	'status' => 'success',
	'tokens' => $Target->get_all_tokens(),
	'owner' => $Target->get_analytics_token()
], JSON_UNESCAPED_UNICODE);