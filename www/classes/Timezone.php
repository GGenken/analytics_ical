<?php include_once $_SERVER['DOCUMENT_ROOT'].'/Calendar/ROOT.php';

/*
 * Класс для формирования синтаксической единицы iCal.
 *
 * VTIMEZONE - оператор, инициализирующий часовой пояс.
 * В случае с мультизональностью пользователей, важный параметр.
 */

include_once ROOT.'/classes/Standard.php';

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
