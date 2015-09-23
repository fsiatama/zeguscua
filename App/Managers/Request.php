<?php

class Request {

	protected $url;

	protected $controller;
	protected $defaultController = 'home';
	protected $action;
	protected $defaultAction = 'index';
	protected $params = array();

	public function __construct($url)
	{
		$this->url = $url;

		$segments = explode('/', $this->getUrl());

		$this->resolveController($segments);
		$this->resolveAction($segments);
		$this->resolveParams($segments);
	}

	public function resolveController(&$segments)
	{
		$this->controller = array_shift($segments);

		if (empty($this->controller))
		{
			$this->controller = $this->defaultController;
		}
	}

	public function resolveAction(&$segments)
	{
		$this->action = array_shift($segments);

		if (empty($this->action))
		{
			$this->action = $this->defaultAction;
		}
	}

	public function resolveParams(&$segments)
	{
		$postParams  = $_POST;
		$urlParams   = $segments;

		array_walk($urlParams, function (&$item) {
			if (!is_array($item)) {
				$item = addslashes(strip_tags($item));
			}
		});

		//var_dump($postParams);
		array_walk($postParams, function (&$item) {
			if (!is_array($item)) {
				$item = addslashes(strip_tags($item));
			}
		});

		//var_dump($postParams);
		$this->params = compact('urlParams', 'postParams');
	}

	public function getUrl()
	{
		return $this->url;
	}

	public function getController()
	{
		return $this->controller;
	}

	public function getControllerClassName()
	{
		return Inflector::camel($this->getController()) . 'Controller';
	}

	public function getControllerFileName()
	{
		return PATH_APP.'Controllers/' . $this->getControllerClassName() . '.php';
	}

	public function getAction()
	{
		return $this->action;
	}

	public function getActionMethodName()
	{
		return Inflector::lowerCamel($this->getAction()) . 'Action';
	}

	public function getParams()
	{
		return $this->params;
	}

	public function execute()
	{
		$controllerClassName = $this->getControllerClassName();
		$controllerFileName  = $this->getControllerFileName();
		$actionMethodName    = $this->getActionMethodName();
		$params              = $this->getParams();

		//d($controllerClassName, $controllerFileName, $actionMethodName);

		$response = new View('404', ['is_template' => false]);

		if ( file_exists($controllerFileName)) {
			
			require $controllerFileName;

			$controller = new $controllerClassName();
			
			if ( method_exists($controller, $actionMethodName) ) {
			
				$response = call_user_func_array([$controller, $actionMethodName], $params);
			}
		}

		$this->executeResponse($response);
	}

	public function executeResponse($response)
	{

		if ($response instanceof Response)
		{
			$response->execute();
		}
		elseif (is_string($response))
		{
			echo $response;
		}
		elseif(is_array($response))
		{
			echo json_encode($response);
		}
		else
		{
			$return = [
				'success' => false,
				'error'   => 'Respuesta no valida '
			];
			exit(json_encode($return));
		}
	}

}