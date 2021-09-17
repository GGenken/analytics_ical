<?php

/*
 * Возврат ошибки
 */

function RAISE($msg = 'undefined') {
	die(json_encode([
		'status' => 'error',
		'details' => $msg
	]));
}
