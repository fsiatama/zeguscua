<?php

use Zeguscua\Repositories\ContactRepo;

class ContactController {

	public function formAction($urlParams, $postParams)
	{
		$contactRepo = new ContactRepo;

		return $contactRepo->formProcess($postParams);
	}

}
