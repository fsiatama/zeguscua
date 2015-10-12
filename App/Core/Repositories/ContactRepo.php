<?php namespace Zeguscua\Repositories;

class ContactRepo {

	public function formProcess($params)
	{
		extract($params);
		
		if (
			empty($email) ||
			empty($phone) ||
			empty($message) ||
			empty($name)
		){
			$result = [
				'success' => false,
				'error'   => 'Incomplete data for this request.'
			];
			return $result;
		}
		
		$message     = \Inflector::cleanInputString($message);
		$name        = \Inflector::cleanInputString($name);
		$email       = \Inflector::cleanInputEmail($email);
		$phone       = \Inflector::cleanInputEmail($phone);
		$is_template = true;

		$arrEmail = [
			[ 'email' => 'sorzanog@yahoo.com', 'name' => 'Nelson Prieto' ],
			[ 'email' => 'fas0980@gmail.com', 'name' => 'Fabian Siatama' ],
		];

		$subject = 'Contactenos VillaZeguscua.com';

		$template = 'emailContact';
		$view = new \View($template, compact('message', 'name', 'email', 'phone', 'is_template'));

		ob_start();

		$view->execute();
		$html = ob_get_clean();

		$result = \Helpers::sendEmail($arrEmail, $html, $subject);
		if (!$result['success']) {
			return $result;
		}

		$result = [
			'success' => true,
			'url' => URL_RAIZ,
			'msg' => 'Gracias por comunicarte, pronto me pondré en contacto contigo'
		];

		return $result;

	}

}
