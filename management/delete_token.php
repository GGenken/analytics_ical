<?php include "../classes/user.php"; # Подключение БД

/******************************
 * Данный скрипт должен быть  *
 * доступен только для ЛК     *
 ******************************/

$Target = @new User(
	(string)$_REQUEST['analytics_token'],
	$connection,
	$type = 'analytics_token',
	$build_ical = false) or RAISE('No username specified');

$TokenID = (string)$_REQUEST['token'];
if ($TokenID > 3 or $TokenID < 0) { RAISE('TokenID out of possible'); }

echo json_encode(['status' => 'success', 'deleted_token' => $Target->delete_token($TokenID)]);