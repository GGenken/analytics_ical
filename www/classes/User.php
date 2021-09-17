<?php include_once $_SERVER['DOCUMENT_ROOT'].'/Calendar/ROOT.php';

include_once ROOT.'/db/db_init.php';

/*
 * Главный класс всей службы, через который осуществляется взаимодействие с БД
 */

include_once ROOT.'/functions/raise.php';
include_once ROOT.'/db/db_init.php';

include_once ROOT.'/classes/Event.php';
include_once ROOT.'/classes/Calendar.php';

include_once ROOT.'/classes/Lesson.php';

class User extends Calendar {
    private array $tokens = [];

    private string $analytics_token = '';
    private string $token_used = '';

    // Конструктор с двумя типами инициализации -
	// через токен Аналитики (административный), или через токен устройства (пользовательский)
    public function __construct($data, $type = 'token', $build_ical = true) {
    	# Два типа инициализации - через токен устройства или через токен Аналитики
    	if ($type == 'token') {
    		$this->token_used = (string)$data;
    		$this->tokens[$data] = [];
		}
    	elseif ($type == 'analytics_token') {
			$this->analytics_token = (string)$data;
		}

    	# Если надо сконструировать расписание, готовим шаблон
    	if ($build_ical) { parent::__construct(); }
	}

	/*                    Пользовательские методы                    */

	// Запрашивает расписание уроков у Аналитики
	// Пользовательский метод
	private function request_lessons() {
		# Обновляем время обращения у конкретного токена
		if (!isset($this->token_used)) { RAISE('Device token unspecified'); }
		$last_used_response = $GLOBALS['DB']->exe("
    		SELECT last_used
			FROM device_tokens
			WHERE device_token = ?
		", 's', [$this->token_used], $fetch_row = True);

		# Если нет времени последнего пользования, прерываем операцию
		if ($last_used_response !== false) {
			$this->tokens[$this->token_used]['last_used'] = $last_used_response['last_used'];
		}
		else { RAISE('Failed to get token last usage time'); }

		# Если прошло немного времени с последнего обращения, возвращаем 304
		date_default_timezone_set('Europe/Moscow');
		if (time() - strtotime($this->tokens[$this->token_used]['last_used']) <= 300) {
			header("HTTP/1.1 304 Not Modified"); die(0);
		}

		# Используем метод get_analytics_token(), так как за кэшем
		# можно обратиться по соответствующему токену Аналитики
		$response = $GLOBALS['DB']->exe("
			SELECT cache_timestamp, lessons_cache
			FROM analytics_data
			WHERE analytics_token = ?
			LIMIT 1
		", 's', [$this->get_analytics_token()], $fetch_row = true);

		# Проверяем, свежий ли кэш в БД
    	if (
			time() - strtotime($response['cache_timestamp']) <= 600
			and
			$response['lessons_cache'] != null
		) {
    		# Да, возвращаем кэш
			$timetable = json_decode($response['lessons_cache'], $associative = true);
		} else {
    		# Нет, запрашиваем расписание у Аналитики, обрабатываем,
			# возвращаем клиенту обработанную версию, сохраняем в кэш сырой ответ
			// TODO: заменить этот тестовый текст на ответ из API Аналитики
			$timetable = '{
				"lessons":[
					{
						"group":"RUS-8-1",
						"subject":"Русский язык",
						"zoom":"https://letovo.zoom.us/j/96619520927",
						"place":"209",
						"tasks_for_lesson":"Прочитать Евгения Онегина",
						"begin":"20210504T124000",
						"end":"20210504T132000"
					},
					{
						"group":"ENG-9-2",
						"subject":"English language",
						"zoom":"https://letovo.zoom.us/j/88005553535",
						"place":"308",
						"tasks_for_lesson":"Read The Catcher in the Rye",
						"begin":"20210504T154000",
						"end":"20210504T162000"
					}
				]
			}';

			# Сохранение кэша в БД
			$GLOBALS['DB']->exe("
				UPDATE analytics_data
				SET cache_timestamp = ?, lessons_cache = ?
				WHERE analytics_token = ?
			", 'sss', [
				date('Y-m-d H:i:s'),
				json_encode(json_decode($timetable), JSON_UNESCAPED_UNICODE),
				$this->get_analytics_token()
			]);

			$timetable = json_decode($timetable, $associative = true);
		}

    	# Если всё прошло успешно, обновляем время последнего обращения по токену устройства
    	$GLOBALS['DB']->exe("
    		UPDATE device_tokens
    		SET last_used = ?
    		WHERE device_token = ?
    	", 'ss', [date('Y-m-d H:i:s'), $this->token_used]);

    	return $timetable;
	}

	// Переводит массив расписания, полученный из request_lessons() в объекты класса Lesson
	// Пользовательский метод
	public function events_setup() {
    	# Получаем массив расписания
		$lessons = $this->request_lessons();

		# Проверяем, есть ли элемент lessons в корне
		if (!isset($lessons['lessons'])) { RAISE('No lessons found'); }

		# Парсим массив (пример структуры), переводя каждый элемент в Lesson
		$lesson_list = [];
		foreach ($lessons['lessons'] as $lesson) {
			$lesson_list[] = @new Lesson(
				$lesson['subject'],
				$lesson['group'],
				$lesson['begin'],
				$lesson['end'],
				$lesson['place'],
				$lesson['zoom'],
				$lesson['tasks_for_lesson']
			) or RAISE('Failed to parse lessons');
		}

		# Переводим объекты Lesson в объекты Event (для составления iCal)
		foreach ($lesson_list as $lesson_object) {
			$this->events[] = @new Event(
				md5($lesson_object->subject.' '.$lesson_object->group.' '.$lesson_object->start).'@student.letovo.ru',
				$lesson_object->subject.', '.$lesson_object->group,
				$lesson_object->start,
				$lesson_object->end,
				$lesson_object->description,
				$lesson_object->link,
				$lesson_object->location
			) or RAISE('Failed to convert lessons into an iCal Format');
		}

		return $this->events;
	}


	/*                    Административные методы                    */

	// Инициализирует токен Аналитики в БД, обязательно для создания токенов устройств
	// Административный метод
	public function init_new_user() {
		# Для регистрации пользователя необходим токен Аналитики, заданный напрямую
		$result = $GLOBALS['DB']->exe('
    		INSERT INTO analytics_data
			(
			 analytics_token
			)
			VALUES(
			 ?
			)
    	', 's', [$this->analytics_token]);

		# Если запрос удался, и ровно одна ячейка была затронута, процесс удался
		# $result может остаться не равным false, и $result->affected_rows может вернуть -1 в случае ошибки с INSERT
		if ($result !== false and $result->affected_rows === 1) { return true; }
		return false;
	}

	// Создаёт и возвращает случайный токен устройства, связанный с токеном Аналитики
	// Административный метод
	public function create_token($description = '') {
		# Случайный токен
		function generate_string($chars = 'abcdefghilkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', $length = 16) {
			$chars_length = strlen($chars);
			$result = '';
			for($char = 0; $char < $length; $char++) { $result .= $chars[mt_rand(0, $chars_length - 1)]; }
			return $result;
		}
		$Token = generate_string();

		# Если пользователь был инициализирован через токен устройства, нельзя создавать другой токен устройства
		if (!isset($this->analytics_token)) { RAISE('Analytics token to create device token is not specified'); }

		# Проверка на превышения кол-ва уже зарегистрированных токенов
		if (count($this->get_all_tokens()) < 10) {
			$result = $GLOBALS['DB']->exe("
				INSERT INTO device_tokens
				(
				 analytics_token,
				 device_token,
				 description
				)
				VALUES(
				 ?,
				 ?,
				 ?
				)
			", 'sss', [$this->analytics_token, $Token, $description]);

			# Если в результате запроса возникла ошибка, то скорее всего
			# нет пары для столбца analytics_token в таблице analytics_data
			if ($result === false) { RAISE('Unregistered Analytics token'); }

			# На случай прочих ошибок
			if ($result->affected_rows !== 1) { return false; }
			return $Token;
		} else { RAISE('Bad token quantity'); return false; }
	}

	// Получает токен Аналитики для запроса расписания
	// Административный метод
	public function get_analytics_token() {
		# Если токен уже имеется - возвращаем сразу
		if ($this->analytics_token !== '') { return $this->analytics_token; }

		# Ищем соответствующий использованному токену устройства токен Аналитики
		# До этого момента можно дойти только лишь при втором способе инициализации пользователя
		$response = $GLOBALS['DB']->exe("
				SELECT analytics_token, last_used
				FROM device_tokens
				WHERE device_token = ?
				LIMIT 1
		", 's', [$this->token_used], $fetch_row = true);

		# Если токен устройства будет неверным, то токен Аналитики не будет найден
		if (isset($response['analytics_token'])) {
			$this->analytics_token = $response['analytics_token'];
		} else {
			RAISE('Failed to find Analytics Token by device token');
		}

		# Сохраняем дату последнего использования применённого токена устройства
		$this->tokens[$this->token_used]['last_used'] = $response['last_used'];

		return $this->analytics_token;
	}

	// Получает все токены устройства по токену Аналитики
	// Административный метод
	public function get_all_tokens() {
    	# Если до этого не был задан/получен токен Аналитики,
		# значит эта административная функция не должна быть доступна
		if (!isset($this->analytics_token)) { RAISE('Cannot get all tokens without Analytics token'); }

    	$response = $GLOBALS['DB']->exe("
    		SELECT device_token, description, last_used
			FROM device_tokens
			WHERE analytics_token = ?
		", 's', [$this->analytics_token]);

    	# Если не получилось сделать запрос, завершаем работу
    	if ($response === false) { RAISE('Failed to get Device tokens'); }

    	# Если список токенов получен, его можно перезаписать
		$this->tokens = [];
    	if ($response == []) { return $this->tokens; }

    	foreach ($response as $token) {
    		$this->tokens[$token['device_token']] = [
    			'description' =>  $token['description'],
				'last_used' => $token['last_used']
			];
		}

    	return $this->tokens;
	}

	// Удаляет токен устройства
	// Административный метод
	public function delete_token($token) {
    	# Сначала получаем все токены и проверяем, есть ли он вообще
    	if (count($this->get_all_tokens()) === 0) { RAISE('Specified Analytics token has no related device tokens'); }
    	if (!isset($this->tokens[$token])) { RAISE('Token not found'); }

    	# Это административная операция, необходим заданный/полученный токен Аналитики
    	if (!isset($this->analytics_token)) { RAISE('Analytics token not specified'); }
    	$response = $GLOBALS['DB']->exe("
    		DELETE FROM device_tokens
			WHERE (
			    analytics_token = ?
			) AND (
			    device_token = ?
			)
    	", 'ss', [$this->analytics_token, $token]);

    	if ($response !== false and $response->affected_rows === 1) { return $token; }
		RAISE('Token found, but failed to delete'); return false;
    }
}