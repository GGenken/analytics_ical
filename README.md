<h1>Letovo Analytics Calendar Maker</h1>
<h4>Микро-служба, позволяющая интегрировать расписание из ЛК в формат iCal</h4>
<hr>

<h2>Алгоритм работы</h2>
Клиент заходит в ЛК, и в нём нажимает кнопку "Подписаться на расписание", после чего запрос направляется к ЛК.<br>
ЛК, в свою очередь, перенаправляет запрос на службу в `create_token.php`, которая создаёт новый токен в БД, ассоциирует его с пользователем и возвращает его. ЛК отображает ссылку на `index.php` с префиксом `webcal://` и параметром `token`, встроенными в ссылку, и в браузере пользователя будет предложено подписаться на календарь.<br>
После этого клиент будет с интервалом обращаться к `index.php` со своим токеном, скрипт будет обращаться к БД за именем пользователя, после чего обратится к ЛК за расписанием, переведёт его из JSON в iCal и вернёт клиенту.<br>

<h2>Структура</h2>
Служба хостится как отдельный сайт третьего/четвёртого домена (`cal.site.ru`/`cal.students.site.ru`). Содержит в себе три типа файлов - открытые, закрытые и полностью локальные.

<h4>Открытые файлы</h4>
К открытым относится `index.php`, к которому обращается клиент для обновления информации. Скрипт делает запрос к ЛК, который в ответ на имя пользователя даёт расписание в формате JSON.
В скрипте ограничена частота обращений для каждого пользователя в ЛК. В качестве входных данных требует лишь токен, с которым у него есть ассоциация в БД с именем пользователя.
В ответ даёт интерпретацию полученного JSON в формате календаря iCal.

<h4>Закрытые файлы</h4>
Файлы управления токенами (директория `management`) - `create_token.php`, `check_tokens.php` и `delete_token.php` - создающий токен, возвращающий токены пользователя и удаляющий токены соответственно.
**К ним должен иметь доступ только ЛК, выступая как интерфейс для пользователя и прокси; __в связи с закрытостью, защита от SQL-инъекций не предусмотрена!__**
<h5>`create_token.php`</h5>
Создаёт 16-символьный токен из цифр и латинских символов разного регистра в БД и ассоциирует его с именем пользователя и описанием. На вход принимает имя пользователя __(str, до 32 символов, обязательный параметр)__ и описание для идентификации устройства, на котором будет использоваться токен __(str, до 255 символов, опциональный параметр)__. Возвращает JSON с именем пользователя, сгенерированным токеном и описанием, сохранёнными в БД. В случае, если с именем пользователя ассоциировано 4 и более токенов, в операции будет отказано.
<h6>`management/create_token.php?username=2024genken.gf&description=iPhone 11 Pro Max`</h6>
```
{
   "status":"success",
   "code":0,
   "user":{
      "name":"2024genken.gf",
      "token":"Rs2sB8ZG3Jou7rVx",
      "description":"iPhone 11 Pro Max"
   }
}
```

<h5>`check_tokens.php`</h5>
Возвращает список токенов, ассоциированных с заданным именем пользователя. Выводит описание, первые четыре символа токена в открытом виде (остальные заменены на звёздочки) и время последнего обращения по этому токену.
<h6>`management/check_tokens.php?username=2024genken.gf`</h6>
```
[
      {
         "description":"iPhone 11 Pro Max",
         "token":"Rs2s************",
         "refreshed":"2000-01-01 00:00:00"
      },
      {
         "description":"XEON-ПК",
         "token":"1234***********",
         "refreshed":"2021-04-24 20:00:56"
      }
]
```

<h5>`delete_token.php`</h5>
Удаляет токены из БД, начинающийся на заданную строку и ассоциированный с заданным именем пользователя. На вход принимает имя пользователя __(str, до 32 символов, обязательный параметр)__, и начальные символы токена __(str **[не список!]**, до 16 символов, опциональный параметр; если не задать или оставить пустым, удалятся все токены пользователя)__. В ответ даёт всю информацию об успешно удалённых токенах в открытом виде.
<h6>`management/delete_token.php?username=2024genken.gf&token_start=Rs2s`</h6>
```{
      "status":"success",
      "code":0,
      "tokens":[
         {
            "username":"2024genken.gf",
            "token":"Rs2sB8ZG3Jou7rVx",
            "refreshed":"2000-01-01 00:00:00",
            "description":"iPhone 11 Pro Max"
         }
      ]
}```

<h4>Полностью локальные файлы</h4>
К ним относится файл `db.php`, содержащий в себе инициализацию подключения к БД. **Он не должен быть доступен извне ни для общей сети, ни для ЛК, а только непосредственно из ФС.**

<h2>Устройство БД</h2>
Служба разработана на БД MySQL, содержащей одну таблицу с четырьмя столбцами:
- `username`, `varchar(32)` - имя пользователя, который использует токен. Использование более четырёх записей в таблице с одним и тем же значением этого столбца не допускается.<br> 
- `token`, `char(16)` - токен, по которому можно определить имя пользователя. Каждое значение в столбце должно быть уникально. Состоит из цифр и латинских символов разного регистра, длина ровно 16 символов.<br>
- `refreshed`, `timestamp` - временная метка последнего запроса по токену, ограничивающая частоту запросов. По умолчанию при создании новой записи устанавливается в `2000-01-01 00:00:00`<br>
- `description`, `tinytext` - описание устройства, на котором используется метка, задаётся пользователем в интерфейсе ЛК **[нужна защита от SQL-инъекций]**, может быть пустым, но не более 255 символов.<br>

Для быстрого создания таблицы используется следующая SQL-запись:
```
create table cal
(
	username varchar(32) null comment 'Username',
	token char(16) null comment 'User''s token',
	refreshed timestamp default '2000-01-01 00:00:00' null comment 'Last refresh',
	description tinytext null comment 'Description of the device that uses this token',
	constraint cal_token_uindex
		unique (token)
)
comment 'Main iCal Service table';
```
