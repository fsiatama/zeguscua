<?php
/**
*@author Fabian Siatama
*clase base para la coneccion a una base de datos
*/

//include(PATH_APP.'adodb5/adodb.inc.php');

//include('adodb/adodb.inc.php');

class Connection
{
	protected $connection;
	private $driver;
	private $host;
	private $database;
	private $username;
	private $password;

    public function __construct($database)
	{
		$ADODB_CACHE_DIR = PATH_APP.'cache';
		
		if (!defined('ADODB_FORCE_VALUE')) {
			//PARA TOMAR LOS VALUES CON CADENA 'null' como NULL de mysql
			define('ADODB_FORCE_VALUE',3);
		}

		$this->setConnection($database);
		
		$this->connection = ADONewConnection($this->driver);
    	$this->connection->Connect(
    		$this->host,
    		$this->username,
    		$this->password,
    		$this->database
    	);

    	/*
    	//ADOdb Performance Monitoring Library
    	$perf =& NewPerfMonitor($this->connection);
		$perf->UI($pollsecs=5);
		*/

    	$this->connection->SetCharSet('utf8');
    	$this->connection->SetFetchMode(ADODB_FETCH_ASSOC);
		//$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
	}
	public function setConnection($database)
	{

		$connections = Helpers::getRequire(PATH_APP.'Config/dbs.php');

		if (empty($connections->$database)) {
			$database = $connections->default;
		}

		$this->driver   = $connections->$database->driver;
		$this->host     = $connections->$database->host;
		$this->database = $connections->$database->database;
		$this->username = $connections->$database->username;
		$this->password = $connections->$database->password;
	}

	public function getConnection()
	{
		return $this->connection;
	}

}
