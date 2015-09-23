<?php namespace Sicex\Repositories;

abstract class BaseRepo {
	
	protected $model;
	protected $modelAdo;
	protected $primaryKey;
	protected $rules;

	public function __construct()
	{
		$this->model      = $this->getModel();
		$this->modelAdo   = $this->getModelAdo();
		$this->primaryKey = $this->getPrimaryKey();
		$this->rules      = $this->getRules();
	}
	abstract protected function getModel();
	abstract protected function getModelAdo();
	abstract protected function getPrimaryKey();
	abstract protected function getRules();
	abstract protected function setData($params, $action);

	protected function getAuditRepo()
	{
		return new AuditRepo;
	}
	
	protected function getColumnMethodName($metod, $columnName)
	{
		return strtolower($metod).ucfirst(strtolower($columnName));
	}

	protected function findPrimaryKey($primaryKey)
	{
		if (empty($primaryKey)) {
			$result = [
				'success' => false,
				'error'   => 'Incomplete data for this request.'
			];
			return $result;
		}
		$methodName = $this->getColumnMethodName('set', $this->primaryKey);
		if (method_exists($this->model, $methodName)) {
			call_user_func_array([$this->model, $methodName], compact('primaryKey'));
		}
		$result = $this->modelAdo->exactSearch($this->model);

		if (!$result['success']) {
			return $result;
		}

		if ($result['total'] != 1) {
			$result = [
				'success' => false,
				'error'   => 'Many or none records matching with this search.'
			];
			return $result;
		}
		return $result;
	}

	protected function create($params, $createAudit = true)
	{
		//extract($params);
		
		$result = $this->setData($params, 'create');
		if (!$result['success']) {
			return $result;
		}

		//insertar registro de auditoria
		if ( $createAudit === true ) {
			$result = $this->createAudit($params);
			if (!$result['success']) {
				return $result;
			}
		}

		$result = $this->modelAdo->create($this->model);
		/*if ($result['success']) {
			return ['success' => true];
		}*/

		return $result;
	}

	protected function modify($params, $createAudit = true)
	{
		$result = $this->setData($params, 'modify');
		if (!$result['success']) {
			return $result;
		}

		//insertar registro de auditoria
		if ( $createAudit === true ) {
			$result = $this->createAudit($params);
			if (!$result['success']) {
				return $result;
			}
		}

		$result = $this->modelAdo->update($this->model);
		if ($result['success']) {
			return ['success' => true];
		}
		return $result;
	}

	protected function delete($params, $createAudit = true)
	{
		$primaryKey = $params[$this->primaryKey];

		//insertar registro de auditoria
		if ( $createAudit === true ) {
			$result = $this->createAudit($params);
			if (!$result['success']) {
				return $result;
			}
		}

		$result = $this->findPrimaryKey($primaryKey);

		if ($result['success']) {
			$result = $this->modelAdo->delete($this->model);
		}

		return $result;
	}

	protected function createAudit($params)
	{
		if (empty($params)) {
			return ['success' => true];
		}

		$auditRepo = $this->getAuditRepo();

		$result = $auditRepo->create($params, false);

		return $result;
	}
}