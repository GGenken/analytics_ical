<?php include_once $_SERVER['DOCUMENT_ROOT'].'/Calendar/ROOT.php';

/*
 * Класс для формирования синтаксической единицы iCal.
 *
 * VEVENT - оператор, инициализирующий событие.
 * Обязательная единица для функционала.
 * Может повторяться.
 */

include_once ROOT.'/classes/Alarm.php';

class Event extends Alarm {
	private string $uid;
	private string $url;
	private string $location;
	private string $description;
	private string $tzid;
	private string $dtstart;
	private string $dtend;
	private string $summary;

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
			'SUMMARY:'.$this->summary,
			'END:VEVENT'
		];
		array_merge($lines, parent::out());
		return $lines;
	}
}
