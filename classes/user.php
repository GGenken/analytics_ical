<?php include "../db.php";

function RAISE($msg = 'undefined') {
	die(json_encode([
		'status' => 'error',
		'details' => $msg
	]));
}

class Standard {
    private $offset_from;
    private $offset_to;
    private $st_name;
    private $start;

    public function __construct($offset_from = '+0030',
                                $offset_to = '+0030',
                                $st_name = 'MSK',
                                $start = '19700101T000000') {
        $this->offset_from = $offset_from;
        $this->offset_to = $offset_to;
        $this->st_name = $st_name;
        $this->start = $start;
    }

    public function out() {
        $lines = [
            'BEGIN:STANDARD',
            'TZOFFSETFROM:'.$this->offset_from,
            'TZOFFSETTO:'.$this->offset_to,
            'TZNAME:'.$this->st_name,
            'DTSATRT:'.$this->start,
            'END:STANDARD'
        ]; return $lines;
    }
}

class Timezone extends Standard {
    private $tzid;
    private $tzurl;
    private $x_lic_location;

    public function __construct($tzid = 'Europe/Moscow',
                                $x_lic_location = 'Europe/Moscow',
                                $tzurl = 'http://tzurl.org/zoneinfo-outlook/Europe/Moscow',
                                $offset_from = '+0030',
                                $offset_to = '+0030',
                                $name = 'MSK',
                                $start = '19700101T000000') {
        $this->tzid = $tzid;
        $this->tzurl = $tzurl;
        $this->x_lic_location = $x_lic_location;
        parent::__construct($offset_from, $offset_to, $name, $start);
    }

    public function out() {
        $lines = [
            'BEGIN:VTIMEZONE',
            'TZID:'.$this->tzid,
            'TZURL:'.$this->tzurl,
            'X-LIC-LOCATION:'.$this->x_lic_location,
        ];
        array_merge($lines, parent::out());
        $lines[] = 'END:VTIMEZONE';
        return $lines;
    }
}

class Alarm {
    private $action;
    private $alarm_description;
    private $trigger;

    public function __construct($action = 'DISPLAY',
                                $alarm_description = '',
                                $trigger = 2) {
        $this->action = $action;
        $this->alarm_description = $alarm_description;
        $this->trigger = (string)$trigger;
    }

    public function out() {
        $lines = [
            'BEGIN:VALARM',
            'ACTION:'.$this->action,
            'TRIGGER:-PT'.$this->trigger.'M'
        ]; return $lines;
    }
}

class Event extends Alarm {
    private $uid;
    private $url;
    private $location;
    private $description;
    private $tzid;
    private $dtstart;
    private $dtend;
    private $summary;

    public function __construct($uid,
    							$summary = 'NOT_GIVEN',
                                $dtstart = '20200101T000000',
                                $dtend = '20200101T000000',
                                $description = '',
                                $url = '',
                                $location = '',
                                $tzid = 'Europe/Moscow',
                                $action = 'DISPLAY',
                                $alarm_description = '',
                                $trigger = 2) {
    	$this->uid = $uid;
        $this->summary = $summary;
        $this->dtstart = $dtstart;
        $this->dtend = $dtend;
        $this->description = $description;
        $this->url = $url;
        $this->location = $location;
        $this->tzid = $tzid;
        parent::__construct($action, $alarm_description, $trigger);
    }

    public function out() {
        $lines = [
            'BEGIN:VEVENT',
            'UID:'.$this->uid,
            'URL:'.$this->url,
            'LOCATION:'.$this->location,
            'DESCRIPTION:'.$this->description,
            'DTSTART;TZID='.$this->tzid.':'.$this->dtstart,
            'DTEND;TZID='.$this->tzid.':'.$this->dtend,
            'SUMMARY:'.$this->summary
        ];
        array_merge($lines, parent::out());
        return $lines;
    }
}

class Calendar extends Timezone {
    private $version = '2.0';
    private $prodid = '-//Letovo School/SelfGovernment/Genken//Analytics iCal Timetable Maker v2.0//RU';
    private $created;
    private $name;
    private $calscale;
    private $refresh_interval;

    protected $events;

    public function __construct($name = 'Школьное расписание',
                                $calscale = 'GREGORIAN',
                                $refresh_interval = 10,
                                $tzid = 'Europe/Moscow',
                                $x_lic_location = 'Europe/Moscow',
                                $tzurl = 'http://tzurl.org/zoneinfo-outlook/Europe/Moscow',
                                $offset_from = '+0030',
                                $offset_to = '+0030',
                                $st_name = 'MSK',
                                $start = '19700101T000000') {
        $this->created = date('Ymd').'T'.date('His');
        $this->name = $name;
        $this->calscale = $calscale;
        $this->refresh_interval = (string)$refresh_interval;

        parent::__construct($tzid, $x_lic_location, $tzurl, $offset_from, $offset_to, $st_name, $start);
    }

    public function out() {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:'.$this->version,
            'PRODID:'.$this->prodid,
            'CREATED:'.$this->created,
            'X-WR-CALNAME:'.$this->name,
            'NAME:'.$this->name,
            'CALSCALE:'.$this->calscale,
            'REFRESH-INTERVAL;VALUE=DURATION:P'.$this->refresh_interval.'M'
        ];
        array_merge($lines, parent::out());
        $this->events_setup();
        foreach ($this->events as &$event) { $lines = array_merge($lines, $event->out()); }
        $lines[] = 'END:VCALENDAR';
        return $lines;
    }

    public function ics($die = True) {
		header('Content-disposition: attachment; filename=index.ics');
		header('Content-type: text/calendar; charset=utf-8');

    	$lines = $this->out();
    	foreach ($lines	as &$line) { echo($line); echo(PHP_EOL);}
    	if ($die) { die(0); }
	}
}

class Lesson {
    public $subject;
    public $group;
    public $start;
    public $end;
    public $location;
    public $link;
    public $description;

    public function __construct($subject,
								$group,
								$start,
								$end,
								$location,
								$link,
								$description) {
    	$this->subject = $subject;
    	$this->group = $group;
    	$this->start = $start;
    	$this->end = $end;
    	$this->location = $location;
    	$this->link = $link;
    	$this->description = $description;
	}
}

class User extends Calendar {
    private $name = '';
    private $tokens = [];
    private $analytics_token = '';
    private $token_used = '';

    public $handler;

    public function __construct($data, $handler,  $type = 'token', $build_ical = true) {
    	$this->handler = $handler;
    	if ($type == 'token') {
    		$this->tokens = [$data => []];
    		$this->token_used = $data;
		}
    	elseif ($type == 'analytics_token') {
			$this->analytics_token = (string)$data;
		}
    	if ($build_ical) { parent::__construct(); }
	}



	public function get_analytics_token() {
		$token = array_keys($this->tokens)[0] or RAISE('Token not found');

		$response = mysqli_fetch_assoc(mysqli_query($this->handler, "
				SELECT username, tokens
				FROM device_tokens
				WHERE JSON_CONTAINS(
					JSON_KEYS(tokens),
					JSON_ARRAY(FROM_BASE64('".base64_encode($token)."')),
					'$'
				)
				LIMIT 1
		"));

		$this->name = @$response['username'] or RAISE('Failed to parse username');

		$this->tokens = @json_decode($response['tokens'], $associative = true) or [];

    	$response = mysqli_query($this->handler, "
			SELECT token
			FROM analytics_tokens
			WHERE username = '".$this->name."'
			LIMIT 1;
		");
    	if (!$response) { RAISE('Failed to request an analytic token from DB'); }

    	$this->analytics_token = @mysqli_fetch_assoc($response)['token'] or RAISE('Failed to find a token in Analytics DB');
    	return $this->analytics_token;
	}

	private function request_lessons() {
		date_default_timezone_set('Europe/Moscow');

    	if ($this->analytics_token == '') { $this->get_analytics_token(); }

    	if (strtotime(date('Y-m-d H:i:s')) - strtotime($this->tokens[$this->token_used]['last_refreshed'])  <= 300) { header("HTTP/1.1 304 Not Modified"); die(0); }
		$this->tokens[$this->token_used]['last_refreshed'] = date('Y-m-d H:i:s');

    	mysqli_query($this->handler, "
    		UPDATE device_tokens
    		SET tokens = '".json_encode($this->tokens)."'
			WHERE username = '".$this->name."'
    	");

		$response = mysqli_query($this->handler, "
			SELECT json, time
			FROM calendar_cache
			WHERE student = (
				SELECT username
				FROM analytics_tokens
				WHERE token = FROM_BASE64('".base64_encode($this->analytics_token)."')
				LIMIT 1
			)
		");

    	$response = mysqli_fetch_assoc($response) or RAISE('Failed to parse cache');

    	if ((strtotime(date('Y-m-d H:i:s')) - strtotime($response['time'])) <= 600) {
			return json_decode($response['json'], $associative = true);
		}
    	else {
    		# Получение расписания из Аналитики
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

			mysqli_query($this->handler, "
				UPDATE calendar_cache
				SET time = '".date('Y-m-d H:i:s')."',
				json = '".json_encode(json_decode($timetable))."'
				WHERE student = (
				SELECT username
					FROM analytics_tokens
					WHERE token = FROM_BASE64('".base64_encode($this->analytics_token)."')
					LIMIT 1
				)
			");

			return json_decode($timetable, $associative = true);
		}
	}

	public function events_setup() {
		$lessons = $this->request_lessons();

		@$lessons['lessons'] or RAISE('No lessons found');
		$lesson_list = [];
		foreach ($lessons['lessons'] as &$lesson) {
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

		foreach ($lesson_list as &$lesson_object) {
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



	public function get_all_tokens() {
    	$response = mysqli_query($this->handler, "
    		SELECT tokens
			FROM device_tokens
			WHERE username = (
				SELECT username
				FROM analytics_tokens
				WHERE token = FROM_BASE64('".base64_encode($this->analytics_token)."')
			)
		");

    	$this->tokens = @json_decode(mysqli_fetch_assoc($response)['tokens'], $associative = true) or [];

    	return $this->tokens;
	}

	public function create_token($description = '') {
		function generate_string($chars = 'abcdefghilkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', $length = 16) {
			$chars_length = strlen($chars);
			$result = '';
			for($char = 0; $char < $length; $char++) { $result .= $chars[mt_rand(0, $chars_length - 1)]; }
			return $result;
		}
		$Token = generate_string();

		if ($description == '') { $description = '\'\''; }
		else { $description = "FROM_BASE64('".base64_encode($description)."')"; }

		if (count($this->get_all_tokens()) < 4) {
			$result = mysqli_query($this->handler, "
				UPDATE device_tokens
				SET tokens = JSON_INSERT(
					tokens,
					'$.".$Token."',
					JSON_INSERT(
						'{}',
						'$.last_refreshed',
						'2020-01-01 00:00:00',
						'$.description',
						".$description."
					)
				)
				WHERE username = (
					SELECT username
					FROM analytics_tokens
					WHERE token = FROM_BASE64('".base64_encode($this->analytics_token)."')
					LIMIT 1
				)
				AND JSON_LENGTH(tokens) < 4;
			");

			if (count($this->tokens) != count($this->get_all_tokens())) { return $Token; }
			else { RAISE('Failed to insert token'); }
		}
		else { RAISE('Bad token quantity'); }

		return $Token;
	}

	public function delete_token($token_id) {
		$Queries =  [];
		$Queries[] = "
			SET @personal_token = FROM_BASE64('" . base64_encode($this->analytics_token) . "');
		";

		$Queries[] = "
			SET @target_token_id = '" . $token_id . "';
		";
		$Queries[] = "
			SET @found_username = (
				SELECT username
				FROM analytics_tokens
				WHERE token = @personal_token
				LIMIT 1
			);
		";
		$Queries[] = "
			SET @tokens_list = (
				SELECT tokens
				FROM device_tokens
				WHERE username = @found_username
			);
			";

		$Queries[] = "
			SET @target_token = (
				SELECT JSON_UNQUOTE(JSON_EXTRACT(
					JSON_KEYS(
						JSON_EXTRACT(@tokens_list, '$')
					),
					CONCAT('$[', @target_token_id, ']')
				))
			);
			";

		$Queries[] = "
			SET @updated_tokens_list = REMOVE_TOKEN(@tokens_list, @target_token);
		";

		$Queries[] = "
			SET @removed = (JSON_LENGTH(@updated_tokens_list) = JSON_LENGTH(@tokens_list)) XOR 1;
		";

		$Queries[] = "
			SET @removed = IF(@removed,
				@target_token,
				'INDEX_NOT_FOUND'
			);
		";

		$Queries[] = "
			SET @removed = IF(@found_username,
				@removed,
				'USERNAME_NOT_FOUND'
			);
		";

		$Queries[] = "
			UPDATE device_tokens
			SET tokens = @updated_tokens_list
			WHERE username = @found_username;
		";

		foreach ($Queries as &$Q) {	mysqli_query($this->handler, $Q); }
		$Query = "
			SELECT JSON_EXTRACT(@updated_tokens_list, '$') AS tokens, @removed AS status;
		";

		$response = mysqli_fetch_assoc(mysqli_query($this->handler, $Query));

		if ($response['status'] == 'INDEX_NOT_FOUND') { RAISE('Such token ID does not exist for this user'); }
		elseif ($response['status'] == 'USERNAME_NOT_FOUND') { RAISE('User not found, check analytics token'); }

		$this->tokens = json_decode($response['tokens'], $associative = true);

		return $response['status'];
	}
}