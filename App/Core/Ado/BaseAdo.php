<?php namespace Sicex\Ado;

use Connection;
use Inflector;

abstract class BaseAdo extends Connection {

	protected $operator;
	protected $model;
	protected $data = [];
	protected $table;
	protected $primaryKey;
	protected $columns = null;
	protected $whereAssignment = null;
	protected $selectedValues = null;

	public function __construct($dbName)
	{
		parent::__construct($dbName);
		$this->setTable();
		$this->setPrimaryKey();
	}

	abstract protected function setData();
	abstract protected function buildSelect();

	public function setWhereAssignment($value)
	{
		$value = ($value === true) ? true : false ;
		$this->whereAssignment = $value;
	}

	public function getWhereAssignment()
	{
		return $this->whereAssignment;
	}

	public function setColumns($columns)
	{
		$this->columns = $columns;
	}

	public function getColumns()
	{
		return $this->columns;
	}

	protected function getOperator()
	{
		return $this->operator;
	}

	protected function setOperator($operator)
	{
		$this->operator = $operator;
	}

	protected function getModel()
	{
		return $this->model;
	}

	protected function setModel($model)
	{
		$this->model = $model;
	}

	protected function getTable()
	{
		return $this->table;
	}

	abstract protected function setTable();

	protected function getPrimaryKey()
	{
		return $this->primaryKey;
	}

	abstract protected function setPrimaryKey();

	public function setSelectedValues($selectedValues)
	{
		$arrValues            = explode(',', $selectedValues);
		$selectedValues       = implode('","', $arrValues);
		$this->selectedValues = '"'.$selectedValues.'"';
	}

	public function getSelectedValues()
	{
		return $this->selectedValues;
	}

	public function paginate($model, $operator, $numRows, $page)
	{
		$this->setModel($model);
		$this->setOperator($operator);

		$conn = $this->getConnection();
		$this->setData();

		$sql = $this->buildSelect();

		$savec = ( empty($ADODB_COUNTRECS) ) ? false : $ADODB_COUNTRECS;
		if ($conn->pageExecuteCountRows) {
			$ADODB_COUNTRECS = true;
		}
		$resultSet = $conn->PageExecute($sql, $numRows, $page);
		$ADODB_COUNTRECS = $savec;

		$result = $this->buildResult($resultSet, false, true);

		return $result;
	}

	protected function search()
	{
		$conn = $this->getConnection();
		$this->setData();

		$sql = $this->buildSelect();
		$resultSet = $conn->Execute($sql);
		$result = $this->buildResult($resultSet);

		return $result;
	}

	public function exactSearch($model)
	{
		$this->setModel($model);
		$this->setOperator('=');
		return $this->search();
	}

	public function likeSearch($model)
	{
		$this->setModel($model);
		$this->setOperator('LIKE');
		return $this->search();
	}

	public function inSearch($model)
	{
		$this->setModel($model);
		$this->setOperator('IN');
		return $this->search();
	}

	public function notInSearch($model)
	{
		$this->setModel($model);
		$this->setOperator('NOTIN');
		return $this->search();
	}

	public function update($model)
	{
		$conn = $this->getConnection();
		$primaryKey = $this->getPrimaryKey();
		$table = $this->getTable();

		$this->setModel($model);
		$this->setData();

		$filter = array();
		foreach($this->data as $key => $value){
			if($value != '' && $key <> $primaryKey){
				$filter[] = $key. ' = "' . $value . '"';
			}
		}
		$id = $this->data[$primaryKey];
		$sql = '
			UPDATE '.$table.' SET
				'.implode(', ',$filter).'
			WHERE '.$primaryKey.' = "'.$id.'"
		';

		//echo '<pre>'.$sql.'</pre>';
		
		$resultSet = $conn->Execute($sql);
		$result = $this->buildResult($resultSet);

		return $result;
	}

	public function delete($model)
	{
		$conn = $this->getConnection();
		$primaryKey = $this->getPrimaryKey();
		$table = $this->getTable();

		$id = $this->data[$primaryKey];
		$sql = '
			DELETE FROM '.$table.' WHERE '.$primaryKey.' = "'.$id.'"
		';
		
		$resultSet = $conn->Execute($sql);
		$result = $this->buildResult($resultSet);

		return $result;
	}

	protected function buildResult(&$resultSet, $insertId = false, $paginate = false)
	{
		$conn = $this->getConnection();
		$result = array();
		
		if(!$resultSet){
			$result['success'] = false;
			$result['error']  = $conn->ErrorMsg();
		}
		else{
			$result['success'] = true;
			$result['total']   = (!$paginate) ? $resultSet->RecordCount() : $resultSet->_maxRecordCount;
			$result['data']    = [];
			if ($insertId !== false) {
				$result['insertId'] = $insertId;
			}
			while(!$resultSet->EOF){
				$result['data'][] = $this->filterRow($resultSet->fields);
				$resultSet->MoveNext();
			}
			/*if (!empty($result['data'])) {
				$result['columns'] = $this->getColumnsType($resultSet);
			}*/
			$resultSet->Close();
		}

		return $result;
	}

	protected function getColumnsType(&$resultSet)
	{
		$fieldTypes = $resultSet->FieldTypesArray();
		reset($fieldTypes);
		$i = 0;
		$elements = [];
		while(list(,$o) = each($fieldTypes)) {
			
			$type = $resultSet->MetaType($o->type);

			$v = ($o) ? $o->name : 'Field'.($i++);
			$v = strip_tags(str_replace("\n", " ", str_replace("\r\n"," ",$v)));

			$elements[] = ['type' => $type, 'col' => $v];
		}

		return $elements;
	}

    /**
     * filterRow
     * 
     * @param array $row contiene un array con los valores de un registro de la entidad que hereda
     *
     *
     * @access protected
     *
     * @return array $row solo con las columnas especificadas en $this->columns.
     */
	protected function filterRow($row)
	{
		$columns = $this->getColumns();
		$model   = $this->getModel();

		if (!is_null($columns) && is_array($columns)) {
			$newRow = [];
			foreach ($columns as $column) {

				$keyName = (is_array($column)) ? $column['key'] : $column;
				$columnKey = (is_array($column)) ? $column['columnKey'] : $column;

				$methodName = 'get' . Inflector::underCamel($columnKey) . 'Attribute';
				//var_dump($column, $keyName, $columnKey);

				if (array_key_exists($columnKey, $row)) {
					$newRow[$keyName] = $row[$columnKey];
				}
				elseif (method_exists($model, $methodName)) {

					$segments   = explode('_', $columnKey);
					$attribute  = array_pop($segments);
					$columnName = implode('_', $segments);

					if (!empty($row[$columnName]) || $row[$columnName] === '0') {
						$response        = call_user_func_array([$model, $methodName], [$row[$columnName]]);
						$newRow[$keyName] = $response;
					}
				}
			}
			$row = $newRow;
		}

		return $row;
	}
}