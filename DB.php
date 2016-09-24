<?php
class DataBase extends PDO
{
	protected $dbh = NULL;

	public function __construct()
	{
		$dsn = 'mysql:host=localhost;dbname=citygram';
		$options = array(
				PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
			);

		try {
			$this->dbh = new PDO($dsn, 'root', 'morteza3120', $options);
			$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->dbh->exec("SET character_set_results=utf8;");
        	$this->dbh->exec("SET character_set_client=utf8;");
        	$this->dbh->exec("SET character_set_connection=utf8;");
        	$this->dbh->exec("SET character_set_database=utf8;");
        	$this->dbh->exec("SET character_set_server=utf8;");
		} catch(PDOException $e) {
			throw new Exception("Error: Connection Error!");
			die();
		}
	}

	public function read($tableName, $fields = array(), $where = array(), $andOr = '', $options = '')
	{
		if ($this->dbh) {
			if (count($fields) > 0) {
				foreach ($fields as $key => $value) {
					$fieldsArray[] = '`' . $value . '`';
				}

				$fieldsText = implode(',', $fieldsArray);
			}
			else {
				$fieldsText = '*';
			}

			if (count($where) > 0) {
				$whereText = 'WHERE (';

				foreach ($where as $key => $value) {
					$whereText .= '`' . $key . '` = :' . $key . ' ' . $andOr . ' ';
				}

				if ($andOr == 'AND') {
					$whereText = substr($whereText, 0, -5) . ')';
				}
				elseif ($andOr == 'OR') {
					$whereText = substr($whereText, 0, -4) . ')';
				}
				else {
					$whereText = substr($whereText, 0, -2) . ')';
				}

				if (strlen($options) > 0) {
					$query = "SELECT {$fieldsText} FROM `{$tableName}` {$whereText} {$options}";
				}
				elseif (strlen($options) == 0) {
					$query = "SELECT {$fieldsText} FROM `{$tableName}` {$whereText}";
				}

				echo '"' . $query . '"' . '<br />';

				$stmt = $this->dbh->prepare($query);

				foreach ($where as $key => $value) {
					$stmt->bindValue(':' . $key, $value);
				}

				$stmt->execute();
			}
			else {
				if (strlen($options) > 0) {
					$query = "SELECT {$fieldsText} FROM `{$tableName}` {$options}";
				}
				elseif (strlen($options) == 0) {
					$query = "SELECT {$fieldsText} FROM `{$tableName}`";
				}

				echo '"' . $query . '"' . '<br />';

				$stmt = $this->dbh->prepare($query);
				$stmt->execute();
			}

			if ($stmt && $stmt->rowCount() > 0) {
				$resultArray = array();
				
				while ($row = $stmt->fetchObject()) {
					$resultArray[] = $row;
				}

				return $resultArray;
			}
			else {
				throw new Exception("Error: Record Not Found!");
			}
		}
	}

	public function create($tableName, $inputs = array())
	{
		if ($this->dbh && count($inputs) > 0) {
			foreach ($inputs as $key => $value) {
				$fields[] = '`' . $key . '`';
				$values[] = ':' . $key;
			}

			$fields = implode(',', $fields);
			$values = implode(',', $values);

			$stmt = $this->dbh->prepare("INSERT INTO `{$tableName}` ($fields) VALUES ($values)");

			foreach ($inputs as $key => $value) {
				$stmt->bindValue(':' . $key, $value);
			}

			$stmt->execute();

			if ($stmt && $stmt->rowCount()) {
				return 'Ba Movafaqiyat Ezafeh Shod';
			}
			else {
				throw new Exception("Error: Khatayi Dar Darj Rokh Dadeh Ast!");
			}
		}
	}

	public function delete($tableName, $where = array())
	{
		if ($this->dbh && count($where) > 0) {
			$whereText = 'WHERE (';

			foreach ($where as $key => $value) {
				$whereText .= '`' . $key . '` = :' . $key . ' AND ';
			}

			$whereText = substr($whereText, 0, -5) . ')';
			$stmt = $this->dbh->prepare("DELETE FROM `{$tableName}` {$whereText}");

			foreach ($where as $key => $value) {
				$stmt->bindValue(':' . $key, $value);
			}

			$stmt->execute();

			if ($stmt && $stmt->rowCount()) {
				return 'Ba Movafaqiyat Hazf Shod';
			}
			else {
				throw new Exception("Error: Khatayi Dar Hazf Rokh Dade Ast!");
			}
		}
	}

	public function update($tableName, $update = array(), $where = array(), $andOr = '')
	{
		if ($this->dbh && count($update) > 0 && count($where) > 0) {
			$whereText = 'WHERE (';

			foreach ($where as $key => $value) {
				$whereText .= '`' . $key . '` = :' . $key . ' ' . $andOr . ' ';
			}

			if ($andOr == 'AND') {
				$whereText = substr($whereText, 0, -5) . ')';
			}
			elseif ($andOr == 'OR') {
				$whereText = substr($whereText, 0, -4) . ')';
			}
			else {
				$whereText = substr($whereText, 0, -2) . ')';
			}

			$updateText = '';

			foreach ($update as $key => $value) {
				$updateText .= '`' . $key . '` = :' . $key . ' , ';
			}

			$updateText = substr($updateText, 0, -3);

			echo '"' . "UPDATE `{$tableName}` SET {$updateText} {$whereText}" . '"'; die();
			$stmt = $this->dbh->prepare("UPDATE `{$tableName}` SET {$updateText} {$whereText}");

			foreach ($update as $key => $value) {
				$stmt->bindValue(':' . $key, $value);
			}

			foreach ($where as $key => $value) {
				$stmt->bindValue(':' . $key, $value);
			}

			$stmt->execute();

			if ($stmt && $stmt->rowCount()) {
				return 'Ba Movafaqiyat Update Shod';
			}
			else {
				throw new Exception("Error: Khatayi Dar Update Rokh Dade Ast!");
			}
		}
	}

	public function closeDataBase()
	{
		$this->dbh = NULL;
	}
}
