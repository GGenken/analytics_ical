<?php

/*
 * Класс для выполнения запросов к БД
 */

class DB {
	private string $host;
	private string $user;
	private string $pass;
	private string $db;

	private $dblink;

	public function __construct($host, $user, $pass, $db) {
		$this->host = $host;
		$this->user = $user;
		$this->pass = $pass;
		$this->db = $db;

		$this->dblink = mysqli_connect($host, $user, $pass, $db);
		$this->dblink->set_charset('utf8mb4_general_ci');
	}

	public function exe($query, $types = '', $params = '', $fetch_row = false) {
		$statement = $this->dblink->prepare($query);
		if ($types and $params) {
			$statement->bind_param($types, ...$params);
		}

		if ($statement->execute()) {
			$result = $statement->get_result();

			if ($result) {
				if ($fetch_row) {
					return $result->fetch_array(MYSQLI_ASSOC);
				}
				return $result->fetch_all(MYSQLI_ASSOC);
			} else { return $statement; }
		} else { return false; }
	}
}
