<?php

class View extends Response {

	protected $template;
	protected $vars = array();
	private   $path;

	public function __construct($template, $vars = array())
	{
		$this->template = $template;
		$this->path     = $this->setPath();
		$this->vars     = $vars;
	}

	/**
	 * @return string
	 */
	private function getTemplate()
	{
		return $this->template;
	}

	private function setPath()
	{
		return PATH_APP.'Views/';
	}

	private function getPath()
	{
		return $this->path;
	}

	private function getTemplateFileName()
	{
		return $this->getPath() . $this->getTemplate() . '.tpl.php';
	}

	private function getTemplateHeaderFileName()
	{
		return $this->getPath() .'htmlComplements/' . $this->getTemplate() . '.header.tpl.php';
	}

	private function getTemplateScriptsFileName()
	{
		return $this->getPath() .'htmlComplements/' . $this->getTemplate() . '.scripts.tpl.php';
	}

	/**
	 * @return array
	 */
	private function getVars()
	{
		return $this->vars;
	}

	public function execute()
	{
		$template         = $this->getTemplate();
		$templateFileName = $this->getTemplateFileName();
		$vars             = $this->getVars();

		//var_dump($templateFileName);

		if ( ! file_exists($templateFileName))
		{
			$return = [
				'success' => false,
				'error'   => 'Vista no existe '. $template
			];
			exit(json_encode($return));
		}

		$templateHeaderFileName  = ( file_exists($this->getTemplateHeaderFileName()) ) ? $this->getTemplateHeaderFileName() : '' ;
		$templateScriptsFileName = ( file_exists($this->getTemplateScriptsFileName()) ) ? $this->getTemplateScriptsFileName() : '' ;

		call_user_func(function () use (
			$templateFileName,
			$templateHeaderFileName,
			$templateScriptsFileName,
			$vars
		) {

			extract($vars);

			ob_start();

			require $templateFileName;

			if (!$is_template) {
				$tpl_content = ob_get_clean();
				$tpl_header  = '';
				$tpl_scripts = '';

				if ( !empty($templateHeaderFileName) ) {
					ob_start();
					require $templateHeaderFileName;
					$tpl_header = ob_get_clean();
				}
				if ( !empty($templateScriptsFileName) ) {
					ob_start();
					require $templateScriptsFileName;
					$tpl_scripts = ob_get_clean();
				}


				//var_dump($tpl_content, $tpl_header, $tpl_scripts);

				require PATH_APP."Views/layout.tpl.php";
			}
		});
	}

}