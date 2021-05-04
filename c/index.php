<?php include '../classes/user.php'; # Подключение класса

/**************************************************
 * Данный скрипт должен быть в папке,             *
 * доступной клиентам извне, см. .htaccess        *
 **************************************************/

$Requester = @new User((string)$_REQUEST['token'],
	$connection,
	$type = 'token') or RAISE('Bad token specified');

$Requester->ics();