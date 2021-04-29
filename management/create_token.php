<?php include "../classes/user.php"; # Подключение БД

/******************************
 * Данный скрипт должен быть  *
 * доступен только для ЛК     *
 ******************************/

$Target = @new User(
	(string)$_REQUEST['username'],
	$type = 'username',
	$build_ical = false) or RAISE('No username specified');

$Description = @(string)$_REQUEST['description'] or '';

echo json_encode(['status' => 'success', 'token' => $Target->create_token($Description)]);