<?php include_once $_SERVER['DOCUMENT_ROOT'].'/Calendar/ROOT.php';

/*
 * Класс для компоновки всех синтаксических единиц iCal в единый файл.
 */

include_once ROOT.'/classes/Timezone.php';

class Calendar extends Timezone {
	private string $version = '2.0';
	private string $prodid = '-//Letovo School/SelfGovernment/Genken//Analytics iCal Timetable Maker v2.0//RU';
	private string $created;
	private string $name;
	private string $calscale;
	private int $refresh_interval;

	protected array $events;

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

	// Метод используется в методе ics() для финальной компоновки расписания в iCal
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
		foreach ($this->events as $event) {
			$lines = array_merge($lines, $event->out());

		}
		$lines[] = 'END:VCALENDAR';

		return $lines;
	}

	// Вывод расписания
	// Используется в объектах дочернего класса User в скриптах обращения пользователей
	public function ics($die = True) {
		header('Content-disposition: attachment; filename=index.ics');
		header('Content-type: text/calendar; charset=utf-8');

		$lines = $this->out();
		foreach ($lines	as $line) { echo($line); echo(PHP_EOL);}
		if ($die) { die(0); }
	}
}
