<?php include '../classes/user.php'; # Подключение класса

/******************************
 * Данный скрипт должен быть  *
 * доступен только для ЛК     *
 ******************************/

$Target = @new User(
	(string)$_REQUEST['analytics_token'],
	$connection,
	$type = 'analytics_token',
	$build_ical = false) or RAISE('No analytics token specified');

echo json_encode($Target->get_all_tokens());