<?php

/*
 * Контейнер для хранения информации об уроке.
 * Создан для удобного перевода полученного ответа от Аналитики в параметры событий.
 */

class Lesson {
	public string $subject;
	public string $group;
	public string $start;
	public string $end;
	public string $location;
	public string $link;
	public string $description;

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
