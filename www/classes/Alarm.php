<?php

/*
 * Класс для формирования синтаксической единицы iCal.
 *
 * VALARM - оператор, инициализирующий уведомления для событий.
 * Важно для функционала, но необязательно для парсера.
 */

class Alarm {
	private string $action;
	private string $alarm_description;
	private string $trigger;

	public function __construct($action = 'DISPLAY',
								$alarm_description = '',
								$trigger = 2) {
		$this->action = $action;
		$this->alarm_description = $alarm_description;
		$this->trigger = (string)$trigger;
	}

	public function out() {
		return [
			'BEGIN:VALARM',
			'ACTION:'.$this->action,
			'TRIGGER:-PT'.$this->trigger.'M'
		];
	}
}
