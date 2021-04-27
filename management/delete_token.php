<?php include "../db.php"; # Подключение БД

/******************************
 * Данный скрипт должнен быть *
 * доступен только для ЛК     *
 ******************************/

# Подавление ошибок, т. к. их обработчики нормально работают
error_reporting(0);

# Достаём обязательный параметр username
$TokenStartSymbols = @(string)$_REQUEST['token_start'] or '';
$UserName = @(string)$_REQUEST['username'] or die(json_encode(['status' => 'error', 'code' => 3, 'details' => ['description' => 'No username specified']]));

# Ищем те токены, которые будут удалены, чтобы потом их вернуть
# Сам запрос: SELECT * FROM cal WHERE (username=FROM_BASE64('MjAyNGdlbmtlbi5nZg==') AND token LIKE FROM_BASE64('YWJjJQ=='))
$Query = "SELECT * FROM cal WHERE (username=FROM_BASE64('".base64_encode($UserName)."') AND token LIKE FROM_BASE64('".base64_encode($TokenStartSymbols."%")."'))";echo $Query;
$Deleted = mysqli_fetch_all(mysqli_query($connectionDB, $Query), $mode = MYSQLI_ASSOC);

# Если ничего не найдено, то выходим и выдаём ошибку
if (count($Deleted) == 0) { die(json_encode(['status' => 'error', 'code' => 4, 'details' => ['description' => 'No suitable tokens found', 'user' => $UserName, 'token_start' => $TokenStartSymbols]])); }

# Меняем запрос с вывода на удаление
$Query = str_replace("SELECT *", "DELETE", $Query);
if (!mysqli_query($connectionDB, $Query)) { die(json_encode(['status' => 'error', 'code' => 5, 'details' => ['description' => 'Failed to delete tokens', 'tokens' => $Deleted]])); }
else { die(json_encode(['status' => 'success', 'code' => 0, 'tokens' => $Deleted])); }