<?php include "db.php"; # Подключение БД

/**************************************************
 * Данный скрипт должен быть в папке,             *
 * доступной клиентам извне, см. .htaccess        *
 **************************************************/

# Эти две строки указывают настройки для календарного клиента. Без них календарь будет открываться в браузере как текст, а не в календарном клиенте
header('Content-disposition: attachment; filename=index.ics');
header('Content-type: text/calendar');

# Подавление ошибок, т. к. их обработчики нормально работают
error_reporting(0);

# Достаём токен
$Token = @$_REQUEST['token'] or die(json_encode(['status' => 'error', 'code' => 4, 'details' => ['description' => 'No token specified']]));

# Проверка на правильность токена, + защита от SQL-инъекций (из тех символов, что мы используем, нельзя инжектить)
$permitted_chars = str_split('abcdefghilkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
foreach (str_split($Token) as $char) { if (!(in_array($char, $permitted_chars))) { die(['status' => 'refused', 'code' => 5, 'details' => ['description' => 'Bad token']]); } }

# Формирование запроса на поиск пользователя по токену
$Query = "SELECT username, refreshed FROM cal WHERE token = '".$Token."'";

# Отправка  запроса
$Info = @mysqli_query($connectionDB, $Query) or die(json_encode(['status' => 'error', 'code' => 6, 'details' => ['description' => 'DB error, failed to grab username']]));

# Перевод запроса в массив
$Info = @mysqli_fetch_array($Info) or die(json_encode(['status' => 'error', 'code' => 7, 'details' => ['description' => 'Failed to decode service DB response']]));

# Достаём время последнего обновления
$LastRefresh = $Info['refreshed'] or die(json_encode(['status' => 'error', 'code' => 8, 'details' => ['description' => 'Grabbing timestamp error']]));

# Установка часового пояса для генерации правильного date
date_default_timezone_set('Europe/Moscow');

# Вычисляем секунды, прошедшие с момента последнего обновления
$TimeDifference = strtotime(date('Y-m-d H:i:s')) - strtotime($LastRefresh) or die(json_encode(['status' => 'error', 'code' => 9, 'details' => ['description' => 'Converting timestamp error']]));

# Если прошло меньше пяти минут с момента последнего обновления, то отменяем запрос, снимая нагрузку с ЛК
//if ($TimeDifference < 300) { header("HTTP/1.1 304 Not Modified"); die(); }

# Достаём имя пользователя
$UserName = $Info['username'] or die(json_encode(['status' => 'error', 'code' => 10, 'details' => ['description' => 'Grabbing username error']]));

/***********************************************
 * Тут должен быть запрос, ответом на          *
 * который должен быть JSON вида:              *
 ***********************************************/
#
# Запрашиваем расписание из ЛК
# $lessons = @file_get_contents('http://cal.api.student,letovo.ru/ics?username='.$UserName) or die(json_encode(['status' => 'error', 'code' => 11, 'details' => ['description' => 'Failed to request timetable']]);
#
# Достаём список уроков из JSON
# $lessons = @json_decode($lessons, $associative = True) or die(json_encode(['status' => 'error', 'code' => 12, 'details' => ['description' => 'Bad Letovo Servers response']]);
#
#
# Прмер ответного JSON
$lessons = json_decode('{
    "lessons":[
        {
            "group":"RUS-8-1",
            "subject":"Русский язык",
            "zoom":"https://letovo.zoom.us/j/96619520927",
            "place":"209",
            "tasks_for_lesson":"Прочитать Евгения онегина",
            "begin":"20210425T124000",
            "end":"20210425T132000"
        },
        {
            "group":"ENG-9-2",
            "subject":"English language",
            "zoom":"https://letovo.zoom.us/j/88005553535",
            "place":"308",
            "tasks_for_lesson":"Read The Cathcer in the Rye",
            "begin":"20210425T154000",
            "end":"20210425T162000"
        }
    ]
}', $associative = True);

# Достаём список уроков из списка из JSON
$lessons = @$lessons['lessons'] or die(json_encode(['status' => 'error', 'code' => 13, 'details' => ['description' => 'No lessons found']]));

# Формируем UID для правильного отображения событий
$UID = date('Ymd').'T'.date('His').':'.$UserName.'@student.letovo.ru';

# Специально не используются готовые библиотеки-шаблонизаторы, т. к. они намного медленнее [источник: https://habrastorage.org/webt/vj/6b/9v/vj6b9vxjppclb717kyortk_9v20.jpeg]
echo('BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Letovo School/SelfGovernment/Genken//Analytics iCal Timetable Maker v2.0//RU
CREATED:'.date('Ymd').'T'.date('His').'
X-WR-CALNAME:Школьное расписание
NAME:Школьное расписание
CALSCALE:GREGORIAN
REFRESH-INTERVAL;VALUE=DURATION:P10M
BEGIN:VTIMEZONE
TZID:Europe/Moscow
TZURL:http://tzurl.org/zoneinfo-outlook/Europe/Moscow
X-LIC-LOCATION:Europe/Moscow
BEGIN:STANDARD
TZOFFSETFROM:+0300
TZOFFSETTO:+0300
TZNAME:MSK
DTSTART:19700101T000000
END:STANDARD
END:VTIMEZONE
');
foreach ($lessons as $lesson) { # Конструктор уроков в ICS
echo('BEGIN:VEVENT
UID:'.md5($lesson['subject'].'_'.$lesson['group']).'-'.$UID.'
BEGIN:VALARM
ACTION:DISPLAY
DESCRIPTION:'.$lesson["subject"].'
TRIGGER:-PT2M
END:VALARM
URL:'.$lesson["zoom"].'
LOCATION:'.$lesson["place"].'
DESCRIPTION:'.$lesson["tasks_for_lesson"].'
DTSTART;TZID=Europe/Moscow:'.$lesson["begin"].'
DTEND;TZID=Europe/Moscow:'.$lesson["end"].'
SUMMARY:'.$lesson["subject"].', '.$lesson['group'].'
END:VEVENT
');}
echo('END:VCALENDAR');
header($http_response_code = 200);

# Формирование запроса о том, что клиент в это время запросил календарь, чтобы
# клиент только немного грузил сервис, и чтобы лишний раз не грузить ЛК
$Query = "UPDATE cal SET refreshed = '".date('Y-m-d H:i:s')."' WHERE token = '".$Token."'";

# Отправка запроса
mysqli_query($connectionDB, $Query);