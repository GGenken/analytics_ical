<?php include "../classes/user.php"; # Подключение БД

/******************************
 * Данный скрипт должен быть  *
 * доступен только для ЛК     *
 ******************************/

$Target = @new User(
	(string)$_REQUEST['username'],
	$type = 'username',
	$build_ical = false) or RAISE('No username specified');

$TokenID = (int)$_REQUEST['token_id'] or RAISE('Bad TokenID');
if ($TokenID > 4 or $TokenID < 1) { RAISE('TokenID out of possible'); }

echo json_encode($Target->delete_token($TokenID));