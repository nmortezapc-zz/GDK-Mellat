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
			$this->dbh = new PDO($dsn, 'root', '', $options);
			$this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->dbh->exec("SET character_set_results=utf8;");
        		$this->dbh->exec("SET character_set_client=utf8;");
        		$this->dbh->exec("SET character_set_connection=utf8;");
        		$this->dbh->exec("SET character_set_database=utf8;");
        		$this->dbh->exec("SET character_set_server=utf8;");
		} catch(PDOException $e) {
			throw new Exception("Error: Connection Error!");
		}
	}

	public function read($tableName, $fields = array(), $where = array(), $operations = '=', $andOr = '', $options = '', $mode = 'm')
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

			$whereArrayCount = count($where);

			if ($whereArrayCount > 0) {
				$whereText = 'WHERE (';

				$andOrArray = explode('-', $andOr);
				$andOrArrayCount = count($andOrArray);

				$operationsArray = explode('-', $operations);
				$operationsArrayCount = count($operationsArray);

				if ($andOr != '' && $andOrArrayCount == $whereArrayCount - 1 && $operationsArrayCount == $whereArrayCount) {

					$i = 0; // for operations
					$j = 0; // for AND OR

					foreach ($where as $key => $value) {
						$whereText .= '`' . $key . '` ' . $operationsArray[$i] . ' :' . $key . ' ' . (isset($andOrArray[$j]) ? $andOrArray[$j] : '') . ' ';
						$i++;
						$j++;
					}
				}
				elseif ($andOr == '' && $andOrArrayCount == $whereArrayCount && $operationsArrayCount == $whereArrayCount) {

					$i = 0; // for operations

					foreach ($where as $key => $value) {
						$whereText .= '`' . $key . '` ' . $operationsArray[$i] . ' :' . $key . '  ';
						$i++;
					}
				}
				else {
					die();
				}
			
				$whereText = substr($whereText, 0, -2) . ')';

				if (strlen($options) > 0) {
					$query = "SELECT {$fieldsText} FROM `{$tableName}` {$whereText} {$options}";
				}
				elseif (strlen($options) == 0) {
					$query = "SELECT {$fieldsText} FROM `{$tableName}` {$whereText}";
				}

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

				$stmt = $this->dbh->prepare($query);
				$stmt->execute();
			}

			if ($stmt && $stmt->rowCount() > 0) {

				switch ((string)$mode) {
					case 's':
						return $stmt->fetchObject();
						break;
					case 'm':
						return $stmt->fetchAll();
						break;
					default:
						exit();
						break;
				}
				
				/*
				while ($row = $stmt->fetchObject()) {
					$resultArray[] = $row;
				}

				return $resultArray;
				*/
			}
			else {
				return array();
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
				return true;
			}
			else {
				throw new Exception("Error: Khatayi Dar Darj Rokh Dadeh Ast!");
			}
		}
	}

	public function delete($tableName, $where = array(), $operations = '=', $andOr = '')
	{
		$whereArrayCount = count($where);

		if ($this->dbh && $whereArrayCount > 0) {
			$whereText = 'WHERE (';

			$andOrArray = explode('-', $andOr);
			$andOrArrayCount = count($andOrArray);

			$operationsArray = explode('-', $operations);
			$operationsArrayCount = count($operationsArray);

			if ($andOr != '' && $andOrArrayCount == $whereArrayCount - 1 && $operationsArrayCount == $whereArrayCount) {

				$i = 0; // for operations
				$j = 0; // for AND OR

				foreach ($where as $key => $value) {
					$whereText .= '`' . $key . '` ' . $operationsArray[$i] . ' :' . $key . ' ' . (isset($andOrArray[$j]) ? $andOrArray[$j] : '') . ' ';
					$i++;
					$j++;
				}
			}
			elseif ($andOr == '' && $andOrArrayCount == $whereArrayCount && $operationsArrayCount == $whereArrayCount) {

				$i = 0; // for operations

				foreach ($where as $key => $value) {
					$whereText .= '`' . $key . '` ' . $operationsArray[$i] . ' :' . $key . '  ';
					$i++;
				}
			}
			else {
				die();
			}
			
			$whereText = substr($whereText, 0, -2) . ')';

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

	public function update($tableName, $update = array(), $where = array(), $operations = '=', $andOr = '')
	{
		$whereArrayCount = count($where);

		if ($this->dbh && count($update) > 0 && $whereArrayCount > 0) {
			$whereText = 'WHERE (';

			$andOrArray = explode('-', $andOr);
			$andOrArrayCount = count($andOrArray);

			$operationsArray = explode('-', $operations);
			$operationsArrayCount = count($operationsArray);

			if ($andOr != '' && $andOrArrayCount == $whereArrayCount - 1 && $operationsArrayCount == $whereArrayCount) {

				$i = 0; // for operations
				$j = 0; // for AND OR

				foreach ($where as $key => $value) {
					$whereText .= '`' . $key . '` ' . $operationsArray[$i] . ' :' . $key . ' ' . (isset($andOrArray[$j]) ? $andOrArray[$j] : '') . ' ';
					$i++;
					$j++;
				}
			}
			elseif ($andOr == '' && $andOrArrayCount == $whereArrayCount && $operationsArrayCount == $whereArrayCount) {

				$i = 0; // for operations

				foreach ($where as $key => $value) {
					$whereText .= '`' . $key . '` ' . $operationsArray[$i] . ' :' . $key . '  ';
					$i++;
				}
			}
			else {
				die();
			}
			
			$whereText = substr($whereText, 0, -2) . ')';

			$updateText = '';

			foreach ($update as $key => $value) {
				$updateText .= '`' . $key . '` = :' . $key . ' , ';
			}

			$updateText = substr($updateText, 0, -3);

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

	public function generalRead($tableName, $preText = '', $fields, $where = array(), $operations = '=', $andOr = '', $options = '', $mode = 'm')
	{
		if ($this->dbh && strlen($fields) > 0) {
			$whereArrayCount = count($where);

			if ($whereArrayCount > 0) {
				$whereText = 'WHERE (';

				$andOrArray = explode('-', $andOr);
				$andOrArrayCount = count($andOrArray);

				$operationsArray = explode('-', $operations);
				$operationsArrayCount = count($operationsArray);

				if ($andOr != '' && $andOrArrayCount == $whereArrayCount - 1 && $operationsArrayCount == $whereArrayCount) {

					$i = 0; // for operations
					$j = 0; // for AND OR

					foreach ($where as $key => $value) {
						$whereText .= '`' . $key . '` ' . $operationsArray[$i] . ' :' . $key . ' ' . (isset($andOrArray[$j]) ? $andOrArray[$j] : '') . ' ';
						$i++;
						$j++;
					}
				}
				elseif ($andOr == '' && $andOrArrayCount == $whereArrayCount && $operationsArrayCount == $whereArrayCount) {

					$i = 0; // for operations

					foreach ($where as $key => $value) {
						$whereText .= '`' . $key . '` ' . $operationsArray[$i] . ' :' . $key . '  ';
						$i++;
					}
				}
				else {
					die();
				}
			
				$whereText = substr($whereText, 0, -2) . ')';

				$query = (strlen($preText) > 0 ? $preText . ' ' : '');

				if (strlen($options) > 0) {
					$query .= "SELECT {$fields} FROM `{$tableName}` {$whereText} {$options}";
				}
				elseif (strlen($options) == 0) {
					$query .= "SELECT {$fields} FROM `{$tableName}` {$whereText}";
				}

				$stmt = $this->dbh->prepare($query);

				foreach ($where as $key => $value) {
					$stmt->bindValue(':' . $key, $value);
				}

				$stmt->execute();
			}
			else {
				$query = (strlen($preText) > 0 ? $preText . ' ' : '');

				if (strlen($options) > 0) {
					$query .= "SELECT {$fields} FROM `{$tableName}` {$options}";
				}
				elseif (strlen($options) == 0) {
					$query .= "SELECT {$fields} FROM `{$tableName}`";
				}

				$stmt = $this->dbh->prepare($query);
				$stmt->execute();
			}

			if ($stmt && $stmt->rowCount() > 0) {

				switch ((string)$mode) {
					case 's':
						return $stmt->fetchObject();
						break;
					case 'm':
						return $stmt->fetchAll();
						break;
					default:
						exit();
						break;
				}
				
				/*
				while ($row = $stmt->fetchObject()) {
					$resultArray[] = $row;
				}

				return $resultArray;
				*/
			}
			else {
				return array();
			}
		}
	}

	public function closeDataBase()
	{
		$this->dbh = NULL;
	}
}
