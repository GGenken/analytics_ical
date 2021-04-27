<?php include "../db.php"; # Подключение БД

/******************************
 * Данный скрипт должен быть  *
 * доступен только для ЛК     *
 ******************************/

# Подавление ошибок, т. к. их обработчики нормально работают
error_reporting(0);

# Достаём обязательный параметр username
$UserName = @(string)$_REQUEST['username'] or die(json_encode(['status' => 'error', 'code' => 2, 'details' => ['description' => 'No username specified']]));
$Description = @(string)$_REQUEST['description'] or '';

# Функция генерации рандомных строк; 61 ^ 16 вариантов токенов
function generate_string($chars = 'abcdefghilkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', $length = 16) {
    $chars_length = strlen($chars);
    $result = '';
    for($char = 0; $char < $length; $char++) { $result .= $chars[mt_rand(0, $chars_length - 1)]; }
    return $result;
}
$Token = generate_string();

# Проверяем, сколько токенов у пользователя; Если 4 и более, то отказываем в создании нового токена
# Сам запрос: SELECT COUNT(token) FROM cal WHERE username=FROM_BASE64('MjAyNGdlbmtlbi5nZg==')
if (mysqli_fetch_assoc(mysqli_query($connectionDB, "SELECT COUNT(token) FROM cal WHERE username=FROM_BASE64('".base64_encode($UserName)."')"))["COUNT(token)"] >= 4) { die(json_encode(['status' => 'error', 'code' => 20, 'details' => ['description' => 'Bad quantity of tokens']])); };

# Формируем запрос на создание пары токен/имя пользователя
# По умолчанию временная метка последнего запроса к сервису стоит на 2020-01-01 00:00:00
# Сам запрос: INSERT INTO cal (username, token, description) values(FROM_BASE64('MjAyNGdlbmtlbi5nZg=='), 'No1SgLIlhNZ3VKzG', FROM_BASE64('aVBob25lIDExIFBybyBNYXg='))
$Query = "INSERT INTO cal (username, token, description) values(FROM_BASE64('".base64_encode($UserName)."'), '".$Token."', FROM_BASE64('".base64_encode($Description)."'))";

# Отправляем запрос на сохранение пары токен/пользователь
if (mysqli_query($connectionDB, $Query)) { die(json_encode(['status' => 'success', 'code' => 0, 'user' => ['name' => $UserName, 'token' => $Token, 'description' => $Description]])); } // Всё хорошо, возвращаем токен
else { die(json_encode(['status' => 'error', 'code' => 3, 'details' => ['description' => 'Unknown DB error']])); }