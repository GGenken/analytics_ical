<?php include "../db.php"; # Подключение БД

/******************************
 * Данный скрипт должен быть  *
 * доступен только для ЛК     *
 ******************************/

# Подавление ошибок, т. к. их обработчики нормально работают
error_reporting(0);

# Достаём обязательный параметр username
$UserName = @(string)$_REQUEST['username'] or die(json_encode(['status' => 'error', 'code' => 2, 'details' => ['description' => 'No username specified']]));

# Формируем запрос на создание пары токен/имя пользователя/описание устройства использующего токен
# Сам запрос: SELECT description, token, refreshed FROM cal WHERE username=FROM_BASE64('MjAyNGdlbmtlbi5nZg==')
$Query = @"SELECT description, token, refreshed  FROM cal WHERE username=FROM_BASE64('".base64_encode($UserName)."') GROUP BY refreshed" or die(json_encode(['status' => 'error', 'code' => 3, 'details' => ['description' => 'Creating query error, check whether username is an str object']]));
echo $Query;

# Отправляем запрос
$response = mysqli_query($connectionDB, $Query);
if (!$response) { die(json_encode(['status' => 'error', 'code' => 4, 'details' => ['description' => 'Unknown DB error']])); } // Проверяем, есть ли ответ

$response = mysqli_fetch_all($response, $mode = MYSQLI_ASSOC);

# Обрезаем токены, т. к. они должны быть видны не полностью
$response = array_map(function($tok) { $tok['token'] = substr($tok['token'], 0, 4).str_repeat('*', strlen($tok['token']) - 4); return $tok; }, $response);

# Возвращаем ответ в формате JSON
die(json_encode($response));