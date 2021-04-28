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
        $this -> offset_from = $offset_from;
        $this -> offset_to = $offset_to;
        $this -> st_name = $st_name;
        $this -> start = $start;
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
        $this -> tzid = $tzid;
        $this -> tzurl = $tzurl;
        $this -> x_lic_location = $x_lic_location;
        parent::__construct($offset_from, $offset_to, $name, $start);
    }

    public function out() {
        $lines = [
            'BEGIN:VTIMEZONE',
            'TZID:'.$this->tzid,
            'TZURL:'.$this->tzurl,
            'X-LIC-LOCATION:'.$this->x_lic_location,
        ];
        array_push($lines, parent::out());
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
        $this -> action = $action;
        $this -> alarm_description = $alarm_description;
        $this -> trigger = (string)$trigger;
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

    public function __construct($summary = 'NOT_GIVEN',
                                $dtstart = '20200101T000000',
                                $dtend = '20200101T000000',
                                $description = '',
                                $url = '',
                                $location = '',
                                $tzid = 'Europe/Moscow',
                                $action = 'DISPLAY',
                                $alarm_description = '',
                                $trigger = 2) {
        $this -> summary = $summary;
        $this -> dtstart = $dtstart;
        $this -> dtend = $dtend;
        $this -> description = $description;
        $this -> url = $url;
        $this -> location = $location;
        $this -> tzid = $tzid;
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
        array_push($lines, parent::out());
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

    private $events;

    public function __construct($events,
    							$name = 'Школьное расписание',
                                $calscale = 'GREGORIAN',
                                $refresh_interval = 10,
                                $tzid = 'Europe/Moscow',
                                $x_lic_location = 'Europe/Moscow',
                                $tzurl = 'http://tzurl.org/zoneinfo-outlook/Europe/Moscow',
                                $offset_from = '+0030',
                                $offset_to = '+0030',
                                $st_name = 'MSK',
                                $start = '19700101T000000') {
    	$this->events = $events;
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
        array_push($lines, parent::out());
        foreach ($this->events as &$event) { array_push($lines, $event.out()); }
        $lines[] = 'END:VCALENDAR';
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

class Token {
	public $token;
	public $last_used;
	public $description;

	public function __construct($token,
								$last_used = '',
								$description = '') {
		$this->token = $token;
		$this->last_used = $last_used;
		$this->description = $description;
	}
}


class User {
    private $name = '';
    private $tokens = [];
    private $events = [];

    public function __construct($data, $type = 'token') {
    	if ($type == 'token') {
    		$this->tokens[] = new Token((string)$data);
		}
    	elseif ($type == 'username') {
			$this->name = (string)$data;
		}
	}

	public function get_full() {
		if ($this->name == '') {

			$response = @mysqli_fetch_all(request(
				"SELECT username, token, refreshed, description FROM cal WHERE username=(SELECT username FROM cal WHERE token=FROM_BASE64('".base64_encode($this->tokens[0]->token)."') LIMIT 1)"
			), MYSQLI_ASSOC) or RAISE('Request failed (all tokens by token)');
			$this->name = @$response[0]['username'] or RAISE('No username in response');
		}
		else {
			$response = @mysqli_fetch_all(request(
				"SELECT token, refreshed, description FROM cal WHERE username=FROM_BASE64('".base64_encode($this->name)."')"
			), MYSQLI_ASSOC) or RAISE('Request failed (all tokens by username)');
		}

		$this->tokens = [];
		foreach	($response as &$token) {
			$this->tokens[] = @new Token(
				$token['token'],
				$last_used = $token['refreshed'],
				$description = $token['description']
			) or RAISE('Foreach tokens failure');
		}

		return $this->tokens;
	}

	public function get_username() {
    	$token = @$this->tokens[0]->token or RAISE('No token specified to find the username');
    	$response = @mysqli_fetch_assoc(request(
    		"SELECT username, refreshed FROM cal WHERE token=FROM_BASE64('".base64_encode($token)."')"
		)) or RAISE('Request failed (username)');
    	$this->tokens[0]->last_used = @$response['refreshed'] or RAISE('Failed to request last refresh');
    	$this->name = @$response['username'] or RAISE('Failed to find a username by token');
    	return $this->name;
	}
}

$gleb = new User('123456789123456', $type = 'token'); var_dump($gleb->get_full());