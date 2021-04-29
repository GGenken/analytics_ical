<?php include '../classes/user.php'; # Подключение класса

/******************************
 * Данный скрипт должен быть  *
 * доступен только для ЛК     *
 ******************************/

$Target = @new User(
	(string)$_REQUEST['username'],
	$type = 'username',
	$build_ical = false) or RAISE('No username specified');

$Tokens = $Target->get_full();

foreach ($Tokens as &$token) {
	$token->token = @substr($token->token, 0, 4).str_repeat('*', strlen($token->token) - 4) or $token->token;
}

echo json_encode($Tokens);