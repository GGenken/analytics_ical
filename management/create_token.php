<?php include "../classes/user.php";

/******************************
 * Данный скрипт должен быть  *
 * доступен только для ЛК     *
 ******************************/

$Target = @new User(
	(string)$_REQUEST['analytics_token'],
	$connection,
	$type = 'analytics_token',
	$build_ical = false) or RAISE('No analytics token specified');

$Description = @(string)$_REQUEST['description'] or '';

echo json_encode(['status' => 'success', 'token' => $Target->create_token($Description)]);