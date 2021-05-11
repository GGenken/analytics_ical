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
    private $tokens = [];
    private $token_descriptions = [];
    private $last_usages = [];

    private $analytics_token = '';
    private $token_used = '';

    public $handler;

    public function __construct($data, $handler,  $type = 'token', $build_ical = true) {
    	$this->handler = $handler;
    	if ($type == 'token') {
    		$this->token_used = (string)$data;
		}
    	elseif ($type == 'analytics_token') {
			$this->analytics_token = (string)$data;
		}
    	if ($build_ical) { parent::__construct(); }
	}



	public function get_analytics_token() {
    	if (!$this->analytics_token == '') { return $this->analytics_token; }

		$response = mysqli_fetch_assoc(mysqli_query($this->handler, "
				SELECT analytics_token, last_used
				FROM device_tokens
				WHERE device_token = FROM_BASE64('".base64_encode($this->token_used)."')
				LIMIT 1
		"));

		date_default_timezone_set('Europe/Moscow');
		if (strtotime(date('Y-m-d H:i:s')) - strtotime($response['last_used']) <= 300) { header("HTTP/1.1 304 Not Modified"); die(0); }

		$this->analytics_token = @$response['analytics_token'] or RAISE('Failed to parse Analytics Token');

    	return $this->analytics_token;
	}

	private function request_lessons() {
		date_default_timezone_set('Europe/Moscow');

    	$this->get_analytics_token();

		$response = mysqli_query($this->handler, "
			SELECT cache_timestamp, lessons_cache
			FROM analytics_data
			WHERE analytics_token = ".$this->get_analytics_token()."
		");

    	$response = mysqli_fetch_assoc($response) or RAISE('Failed to parse cache');

    	if (((strtotime(date('Y-m-d H:i:s')) - strtotime($response['cache_timestamp'])) <= 600) and ($response['lessons_cache'] != null)) {
			$timetable = json_decode($response['lessons_cache'], $associative = true);
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
				UPDATE analytics_data
				SET cache_timestamp = '".date('Y-m-d H:i:s')."',
				lessons_cache = FROM_BASE64('".base64_encode(json_encode(json_decode($timetable)))."')
				WHERE analytics_token = '".$this->get_analytics_token()."'
			");

			$timetable = json_decode($timetable, $associative = true);
		}

    	$response = mysqli_query($this->handler, "
    		UPDATE device_tokens
    		SET last_used = '".date('Y-m-d H:i:s')."'
    		WHERE device_token = FROM_BASE64('".base64_encode($this->token_used)."')
    	");

    	return $timetable;
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



	public function get_all_tokens($json = false) {
    	$response = mysqli_query($this->handler, "
    		SELECT device_token, description, last_used
			FROM device_tokens
			WHERE analytics_token = FROM_BASE64('".base64_encode($this->get_analytics_token())."')
		");

    	$response = mysqli_fetch_all($response);
    	if ($response == []) { $this->tokens = []; return $this->tokens; }

    	$this->tokens = [];
    	foreach ($response as $token) {
    		$this->tokens[] = @$token[0];
    		$this->token_descriptions[] = @$token[1];
    		$this->last_usages[] = @$token[2];
		}

    	if ($json) {
    		return [
    			'tokens' => $this->tokens,
				'descriptions' => $this->token_descriptions,
				'usages' => $this->last_usages
			];
		}

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
				INSERT INTO device_tokens
				(
				 analytics_token,
				 device_token,
				 description
				)
				values(
				 '".$this->get_analytics_token()."',
				 '".$Token."',
				 ".$description."
				)
			");

			if (count($this->tokens) != count($this->get_all_tokens())) { return $Token; }
			else { RAISE('Failed to insert token'); }
		}
		else { RAISE('Bad token quantity'); }

		return $Token;
	}

	public function delete_token($token) {
    	if (count($this->get_all_tokens()) == 0) { RAISE('Nothing to delete'); }
    	if (!in_array($token, $this->tokens)) { RAISE('Token not found'); }

    	$response = mysqli_query($this->handler, "
    		DELETE FROM device_tokens
			WHERE (
			    analytics_token = '".$this->analytics_token."'
			) AND (
			    device_token = FROM_BASE64('".base64_encode($token)."')
			)
    	");

    	if (count($this->tokens) == count($this->get_all_tokens())) { RAISE('Count of tokens is the same'); }

    	if ($response) { return $token; }
    	else { RAISE('Failed to delete token'); }
    }
}