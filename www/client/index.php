<?php include_once $_SERVER['DOCUMENT_ROOT'].'/Calendar/ROOT.php';

include_once ROOT.'/classes/user.php';

include_once ROOT.'/functions/raise.php';

/*
 * Отправляет расписание в ответ на токен устройства
 */

if (!isset($_REQUEST['token'])) { RAISE('Device token not specified'); }

$Requester = @new User(
	(string)$_REQUEST['token'],
	$type = 'token'
) or RAISE('Unknown error');

$Requester->ics();