<?php

/**
* ClassName
*
* @author   Fabian Siatama
* 
* se definien los metodos para la traduccion de los textos
*/

class Lang
{
	private static $fileName;
	private static $segments;

	private static function getPath()
	{
		$lang = (empty($_SESSION['lang'])) ? 'es' : $_SESSION['lang'] ;
		return PATH_APP.'Lang/'.$lang.'/';
	}

	private static function setFileName($group)
	{
		$path = static::getPath();

		static::$segments = explode('.', $group);

		$file = array_shift(static::$segments);

		static::$fileName = $path.$file.'.php';
		
	}

	public static function get($group, $replace = [])
	{
		static::setFileName($group);

		$lines = Helpers::getRequire(static::$fileName);

		$line  = Helpers::arrayGet($lines, implode('.', static::$segments));

		$line  = static::makeReplacements($line, $replace);

		return $line;
		
	}

	public static function makeReplacements($line, array $replace)
	{

		foreach ($replace as $key => $value)
		{
			$line = str_replace(':'.$key, $value, $line);
		}

		return $line;
		
	}
		
}
