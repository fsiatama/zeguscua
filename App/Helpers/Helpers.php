<?php

use Apfelbox\FileDownload\FileDownload;
use Sicex\Repositories\sicex_r\InfotablaRepo;
/**
* ClassName
*
* @author   Fabian Siatama
*
* Se definen varias utilidades comunes a toda la aplicacion
*/
class Helpers
{
	public static function getDateTimeNow()
	{
		return date('Y-m-d H:i:s');
	}

	public static function getDateNow()
	{
		return date('Y-m-d');
	}

	public static function getDate($string)
	{
		return DateTime::createFromFormat('Y-m-d', $string);
	}

	public static function toDays($timestamp)
	{
		return round(719528 + ($timestamp)/(60*60*24));
	}

	public static function arraySortByValue($key, $desc = false)
	{
		return function ($a, $b) use ($key, $desc) {
			$operator = ($desc) ? -1 : 1 ;
			return $operator * strnatcmp($a[$key], $b[$key]);
		};
	}

	public static function arrayGet($array, $key)
	{
		if (is_null($key)) return $array;

		if (isset($array[$key])) return $array[$key];

		foreach (explode('.', $key) as $segment)
		{
			if ( ! is_array($array) || ! array_key_exists($segment, $array))
			{
				return '';
			}

			$array = $array[$segment];
		}

		return $array;
	}

	public static function getRequire($path)
	{
		if (file_exists($path)) {
			return require $path;
		}
		exit("File does not exist at path {$path}");
	}

	public static function filterValuesToArray($value)
	{
		$arrFilters = explode('||', $value);
		$arrFields  = [];

		foreach($arrFilters as $key => $filter) {
			$field = explode(':', $filter);

			$arrFields[$field[0]] = $field[1];
		}

		return $arrFields;
	}

	public static function findKeyInArrayMulti($array, $key, $value)
	{
		foreach ($array as $subarray){

			if (isset($subarray[$key]) && $subarray[$key] == $value) {
				return $subarray;
			}
		}

		return false;
	}

	public static function filterKeyInArrayMulti($array, $key, $value)
	{
		$arr = [];
		foreach ($array as $subarray){

			if (isset($subarray[$key]) && $subarray[$key] == $value) {
				$arr[] = $subarray;
			}
		}

		return $arr;
	}

	public static function arrayColumn( array $array, $column_key, $index_key = null )
	{
		$result = [];
		foreach ($array as $key => $row) {
			if ( isset($row[$column_key]) ) {

				$result[ $index_key ? $row[$index_key] : $key ] = $row[$column_key];

			}
		}
		return $result;
	}

	public static function getArrayColModel(array $arrData)
	{
		$result   = [];
		$xtype    = '';
		$renderer = '';
		$align    = '';

		foreach ($arrData as $key => $row) {

			switch ($row['type']) {
				case 'date' :
					$xtype  = 'datecolumn';
					$renderer = 'dateFormat';
				break;

				case 'float' :
					$xtype    = 'numbercolumn';
					$renderer = 'unsignedFormat';
					$align    = 'right';
				break;

				case 'integer' :
					$xtype    = 'numbercolumn';
					$renderer = 'integerFormat';
					$align    = 'right';
				break;
				
				default :
					$xtype    = 'gridcolumn';
					$renderer = '';
					$align    = 'left';
				break;
			}

			$result[] = [
				'dataIndex' => $row['alias'],
				'header'    => $row['name'],
				'hidden'    => false,
				'xtype'     => $xtype,
				'renderer'  => $renderer,
				'align'     => $align,
			];
		}
		return $result;
	}

	public static function getArrayStore(array $arrData)
	{
		$result = [];
		foreach ($arrData as $key => $row) {
			if ($row['type'] == 'date') {
				$result[] = [
					'dateFormat' => 'Y-m-d',
					'name'       => $row['alias'],
					'type'       => 'date'
				];
			} else {
				$result[] = [
					'name' => $row['alias'],
					'type' => 'string'
				];
			}
			
		}
		return $result;
	}

	public static function download($fileName, $deleteFile = true)
	{
		$filePath = PATH_REPORTS . $fileName;

		$fileDownload = FileDownload::createFromFilePath($filePath);

		$result = $fileDownload->sendDownload($fileName);

		if ($deleteFile) {
			unlink($filePath);
		}
		return $result;
	}

	public static function uploadImage($name, $fileName, $dir)
	{
		$result         = new stdClass();
		$file           = $_FILES[$name];
		$tamano         = $file['size'];
		$info           = getimagesize($file['tmp_name']);
		$tmp            = $file['name'];
		$ext            = substr(strrchr($file['name'], '.'), 1);
		$nombre_archivo = md5($name.$fileName).".".$ext;
		$contentType    = $info['mime'];

		if ($tamano > 1000000) {
			$result->reason		= "You can upload images with weights greater than 1Mb";
			$result->success    = false;

		} elseif ($contentType =='image/jpeg' || $contentType =='image/gif' || $contentType =='image/png' ) {

			if(isset($_FILES[$name]) && move_uploaded_file($file['tmp_name'], $dir.$nombre_archivo)){

				list($width, $height, $type, $attr) = getimagesize($dir.$file['name']);
				$result->newImage	= $nombre_archivo;
				$result->imageWidth	= $width;
				$result->imageHeight= $height;
				$result->reason		= $dir.$nombre_archivo;
				$result->success    = true;
			} else {

				$result->reason		= "Unable to Upload";
				$result->success    = false;
			}
		} else {
			
			$result->reason		= "Invalid File in ".$tmp.". Please only upload images files";
			$result->success    = false;
		}
		return $result;
	}

	/**
	 * sendEmail
	 * 
	 * @param mixed \array [ 'email' => 'ppp@domain.com', 'name' => 'Jhon Doe' ].
	 * @param mixed $message       Description.
	 * @param mixed $subject       Description.
	 * @param mixed \array $arrAttachedFile  ['file1', 'file2', ....].
	 *
	 * @access public
	 * @static
	 *
	 * @return mixed Value.
	 */
	public static function sendEmail(array $arrEmail , $message, $subject, array $arrAttachedFile = [])
	{
		$mail = new PHPMailer;

		$mail->isSMTP();
		//Enable SMTP debugging
		// 0 = off (for production use)
		// 1 = client messages
		// 2 = client and server messages
		$mail->SMTPDebug = 0;
		//Ask for HTML-friendly debug output
		//$mail->Debugoutput = 'html';
		//Set the hostname of the mail server
		$mail->Host = 'mail.paulacastano.com';
		//Set the SMTP port number - likely to be 25, 465 or 587
		$mail->Port = 25;

		//Whether to use SMTP authentication
		$mail->SMTPAuth = true;

		$mail->Username = 'contacto@paulacastano.com';
		
		$mail->Password = 'P4ul4C4st4no';

		//Set who the message is to be sent from
		$mail->setFrom('contacto@paulacastano.com', 'Paula Castano.');
		//Set an alternative reply-to address
		//$mail->addReplyTo('replyto@example.com', 'First Last');
		//Set who the message is to be sent to
		foreach ($arrEmail as $key => $row) {

			if ( !empty($row['email']) ) {
				
				$name = ( ! empty($row['name'] ) ) ? $row['name'] : '' ;

				$mail->addAddress($row['email'], $name);
			}
		}
		//Set the subject line
		$mail->Subject = $subject;
		//Read an HTML message body from an external file, convert referenced images to embedded,
		//convert HTML into a basic plain-text alternative body
		$mail->msgHTML($message);
		//Replace the plain text body with one created manually
		//$mail->AltBody = 'This is a plain-text message body';
		//Attach an image file
		foreach ($arrAttachedFile as $file) {
			if(is_file($file)){
				$mail->addAttachment($file);
			}
		}
		//activar utf8 para acentos
		$mail->CharSet = "UTF-8";
		//$mail->Encoding = "quotedprintable";
		
		//send the message, check for errors
		if (!$mail->send()){
			return [
				'success' => false,
				'error'   => 'Mailer Error: ' . $mail->ErrorInfo
			];
		}
		
		return [
			'success' => true
		];
	}

}
