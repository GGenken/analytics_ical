<?php include_once $_SERVER['DOCUMENT_ROOT'].'/Calendar/ROOT.php';

/*
 * Инициализация подключения к БД
 */

include_once ROOT.'/db/db.php';
include_once ROOT.'/db/db_psw.php';
include_once ROOT.'/functions/raise.php';

$GLOBALS['DB'] = new DB(DB_HOST, DB_USER, DB_PASS, DB_DATABASE);

// $GLOBALS['DB_EXE'] = function ($query, $types = '', $params = '') { return $GLOBALS['DB']->exe($query, $types, $params); };

/*
$GLOBALS['DB_EXE']('SELECT * FROM items');
                     vs
$GLOBALS['DB']->exe('SELECT * FROM items');
*/
