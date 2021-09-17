<?php

/*
 * Класс для формирования синтаксической единицы iCal.
 *
 * STANDARD - оператор, устанавливающий часовой пояс по умолчанию.
 * Необязательный параметр, нужен для надёжности и совместимости.
 */

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
		return [
			'BEGIN:STANDARD',
			'TZOFFSETFROM:'.$this->offset_from,
			'TZOFFSETTO:'.$this->offset_to,
			'TZNAME:'.$this->st_name,
			'DTSATRT:'.$this->start,
			'END:STANDARD'
		];
	}
}
