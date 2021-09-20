# Letovo Analytics Calendar Maker
## Служба, переводящая расписание из ЛК в формат iCal
<hr>

## Принцип работы
Служба является мостом между Аналитикой и клиентом, управляя токенами устройств, с помощью которых можно получить расписание через эту службу и частотой обращений по этим токенам, а также кэширует ответы Аналитики. Служба хостится как отдельный сайт третьего/четвёртого домена (`cal.site.ru`/`cal.students.site.ru`).
<br>

## Функционал
Служба выполняет две функции - клиентская (ответ на запрос расписания) и административная (создание/просмотр/удаление токенов).

## Файлы
Файлы управления токенами (директория `management`) - осуществляют административное взаимодействие.
**К ним должен иметь доступ только ЛК, выступая как интерфейс для пользователя.**
Файл клиента (директория `client`) - взаимодействует с клиентом.




### `/www/management/`


#### `new_user.php`
Регистрирует новый токен Аналитики, к которому можно будет привязать токены устройств. На вход принимает только токен Аналитики.

##### `new_user.php?analytics_token=AAjq5IDTMD3lfrorRQiUous8MhxeOI4M`
```
{
    "status": "success",
    "registered_token": "AAjq5IDTMD3lfrorRQiUous8MhxeOI4M"
}
```


#### `create_token.php`
Создаёт токен в БД и ассоциирует его с токеном Аналитики и описанием. На вход принимает имя пользователя и описание устройства.
Возвращает JSON со сгенерированным токеном, ассоциированным в БД с заданным токеном Аналитики. В случае, если с именем пользователя ассоциировано 10 и более токенов, в операции будет отказано.

##### `create_token.php?analytics_token=AAjq5IDTMD3lfrorRQiUous8MhxeOI4M&description=Ноутбук`
```
{
    "status": "success",
    "token": "glADp6VrSTY7Yg3a"
}
```


#### `check_tokens.php`
Возвращает список токенов, ассоциированных с заданным именем пользователя. Выводит описания, токены в открытом виде и время последнего обращения по ним вместе с соответствующим токеном Аналитики.

###### `check_tokens.php?analytics_token=31ZWzYYMGZIkkpYo31ZWzYYMGZIkkpYo`
```
{
    "status": "success",
    "tokens": {
        "eSzMCsalbRERQqxC": {
            "description": "iPhone 8",
            "last_used": "1980-01-01 00:00:00"
        },
        "glADp6VrSTY7Yg3a": {
            "description": "Ноутбук",
            "last_used": "1980-01-01 00:00:00"
        },
        "YM2SzT5dfLyZkF8h": {
            "description": "Smart Watch",
            "last_used": "1980-01-01 00:00:00"
        }
    },
    "owner": "AAjq5IDTMD3lfrorRQiUous8MhxeOI4M"
}
```


#### `delete_token.php`
Удаляет заданный токен устройства, привязанный к заданному токену Аналитики.
В ответ даёт удалённый токен в открытом виде.

###### `delete_token.php?analytics_token=AAjq5IDTMD3lfrorRQiUous8MhxeOI4M&token=eSzMCsalbRERQqxC`
```
{
    "status": "success",
    "deleted_token": "eSzMCsalbRERQqxC"
}
```



### `/client/`
#### `index.php`
Возвращает расписание, полученное от Аналитики или из кэша в формате iCal. На вход принимает токен устройства.

#####`index.php?token=glADp6VrSTY7Yg3a`
```
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Letovo School/SelfGovernment/Genken//Analytics iCal Timetable Maker v2.0//RU
CREATED:20210920T182355
X-WR-CALNAME:Школьное расписание
NAME:Школьное расписание
CALSCALE:GREGORIAN
REFRESH-INTERVAL;VALUE=DURATION:P10M
BEGIN:VEVENT
UID:b3a5aec9306c839df83d77f8ad56bba0@student.letovo.ru
URL:https://letovo.zoom.us/j/96619520927
LOCATION:209
DESCRIPTION:Прочитать Евгения Онегина
DTSTART;TZID=Europe/Moscow:20210504T124000
DTEND;TZID=Europe/Moscow:20210504T132000
SUMMARY:Русский язык, RUS-8-1
END:VEVENT
END:VCALENDAR
```

<hr>


## БД
Для быстрого создания таблицы используется следующая SQL-запись:
```
create table if not exists analytics_data
(
    analytics_token char(32)                                not null comment 'A token used to identify a user in Analytics'
        primary key,
    lessons_cache   text                                    null comment 'Cached JSON data received from Analytics',
    cache_timestamp timestamp default '2020-01-01 00:00:00' not null comment 'A timestamp when the lessons were cached'
)
    comment 'Table that keeps Analytics tokens and cache received from Analytics';

create table if not exists device_tokens
(
    analytics_token char(32)                                not null comment 'Token used to access timetable from Analytics',
    device_token    char(16)                                not null comment 'Token used by device to access the timetable',
    last_used       timestamp default '1980-01-01 00:00:00' not null comment 'Timestamp when token was last used',
    description     tinytext                                null comment 'Description of the device that uses the token',
    constraint device_tokens_analytics_token_device_token_uindex
        unique (analytics_token, device_token),
    constraint device_tokens_analytics_data_analytics_token_fk
        foreign key (analytics_token) references analytics_data (analytics_token)
            on update cascade on delete cascade
)
    comment 'A table for user''s device tokens to contact between service and client';
```

<hr>

## Синтаксис iCal
### Формат тегов
Каждый тег размещён на отдельной строке. Тег может быть либо оперативным и указывать на начало заголовка _(например, `BEGIN:VCALENDAR`)_, или присваивающими _(например, `VERSION:2.0`)_.
Рассмотрим пример, который возвращается тестовым JSON, встроенным в `index.php`.

- `BEGIN:VCALENDAR` - оператор, сообщающий парсеру о начале календарного файла. В случае с подписными и локальными календарями, этот тег может быть в единственном количестве, второй и последующие будут проигнорированы парсером.
- `VERSION:2.0` - версия календаря. Данный тег является обязательным.
- `PRODID:-//Letovo School/SelfGovernment/Genken//Analytics iCal Timetable Maker v2.0//RU` - информация о генераторе этого файла в формате `-//[организация]//[продукт]//[язык]`. Данный тег является обязательным.
- `CREATED:20210426T194455` - временная метка создания календарного файла в формате `YYYYMMDD\THHMMSS`. Необязательный параметр.
- `X-WR-CALNAME:Школьное расписание` - название календаря (для устаревших клиентов Microsoft), обязательный параметр.
- `NAME:Школьное расписание` - название календаря (как прописано в RFC). Обязательный параметр.
- `CALSCALE:GREGORIAN` - формат календаря (японский/хинди/etc.). Необязательный параметр.
- `REFRESH-INTERVAL;VALUE=DURATION:P10M` - частота обновления подписного календаря. Не всегда рабочий параметр, т. к. время задаётся клиентом, но важный для устаревших клиентов.
- `BEGIN:VTIMEZONE` - оператор, сообщающий парсеру о начале инициализации часового пояса. В случае с мультизональностью пользователей, важный параметр.
- `TZID:Europe/Moscow` - ID часового пояса. Некоторые современные клиенты могут инициализировать часовой пояс по этому параметру, но он также нужен при указании часового пояса в событиях. Необязательный, но важный параметр.
- `TZURL:http://tzurl.org/zoneinfo-outlook/Europe/Moscow` - URL, подтверждающий информацию о часовой зоне. Необязательный, но важный параметр.
- `X-LIC-LOCATION:Europe/Moscow` - то же, что и `TZID`, но добавленный для совместимости с некоторыми клиентами.
- `BEGIN:STANDARD` - оператор, сообщающий парсеру о начале установки часового пояса по умолчанию. Необязательный параметр, нужен для надёжности и совместимости.
- `TZOFFSETFROM:+0300` - начало диапазона отклонения от Гринвича (`UTC+0000`). Необязательный параметр.
- `TZOFFSETTO:+0300` - конец диапазона отклонения от Гринвича (`UTC+0000`). Необязательный параметр.
- `TZNAME:MSK` - название часового пояса по умолчанию, нужен некоторым клиентам. Необязательный параметр.
- `DTSTART:19700101T000000` - дата начала отображения событий, помеченных с этим часовым поясом. Необязательный параметр.
- `END:STANDARD` - оператор, сообщающий парсеру об окончании инициализации часового пояса по умолчанию. Необязательный параметр.
- `END:VTIMEZONE` - оператор, сообщающий парсеру об окончании инициализации часового пояса.
- `BEGIN:VEVENT` - оператор, сообщающий парсеру об окончании инициализации события. Может повторяться.
- `UID:7d199ccfb4e2f634565ff11209e83b3a-20210426T194455:2024genken.gf@student.letovo.ru` - уникальный ID события, введён для устранения дубликатов событий при пересылке и случайном дублировании. **Для нелокальных календарей обязателен, в случае отсутствия событие проигнорируется парсером и не отобразится.** В данном случае генератор составляет его из MD5 названия урока и группы, времени генерации события и почты. Обязательный параметр.
- `BEGIN:VALARM` - оператор, сообщающий парсеру о начале инициализации уведомления. Важно, но необязательно.
- `ACTION:DISPLAY` - форма уведомления (баннер/звук). Необязательный параметр.
- `DESCRIPTION:Русский язык` - текст уведомления. Необязательный параметр.
- `TRIGGER:-PT2M` - условие уведомления. В нашем случае, это момент за две минуты до события. Необязательный параметр.
- `END:VALARM` - оператор, сообщающий парсеру об окончании инициализации уведомления.
- `URL:https://letovo.zoom.us/j/96619520927` - URL события (технически ни на что не влияет, но является частью описания события). Необязательный параметр.
- `LOCATION:209` - место события. Необязательный, но важный параметр.
- `DESCRIPTION:Прочитать Евгения онегина` - описание события. Необязательный параметр.
- `DTSTART;TZID=Europe/Moscow:20210425T124000` - начало события. Обязательный параметр.
- `DTEND;TZID=Europe/Moscow:20210425T132000` - окончание события. Обязательный параметр.
- `SUMMARY:Русский язык, RUS-8-1` - главное название события. Обязательный параметр.
- `END:VEVENT` - оператор, сообщающий парсеру об окончании инициализации события.
- `END:VCALENDAR` - оператор, сообщающий парсеру о конце календарного файла. Как правило, все строки после него игнорируются парсером.