<?php include_once $_SERVER['DOCUMENT_ROOT'].'/Calendar/ROOT.php';

include_once ROOT.'/classes/User.php';

include_once ROOT.'/functions/raise.php';

/*
 * Создаёт случайный токен устройства,
 * связывает его с указанным токеном Аналитики
 */

if (!isset($_REQUEST['analytics_token'])) { RAISE('Analytics token not specified'); }

$Target = @new User(
	(string)$_REQUEST['analytics_token'],
	$type = 'analytics_token',
	$build_ical = false
) or RAISE('No analytics token specified');

$Description = @(string)$_REQUEST['description'] or '';

echo json_encode(['status' => 'success', 'token' => $Target->create_token($Description)]);