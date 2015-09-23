<?php

/**
* ClassName Validator
*
* @author   Fabian Siatama
*
* Se definen metodos de validacion para la data que se insertara en cualquier entidad del modelo
* se implementa basado en la clase Validator de laravel
*/
class Validator
{

	private $entity;
	private $data;
	private $rules;
	private $messages = [];
	private $implicitRules = [
		'Required', 'RequiredWith', 'RequiredWithAll', 'RequiredWithout', 'RequiredWithoutAll', 'RequiredIf', 'Accepted'
	];
	
	public function __construct($entity, array $data, array $rules)
	{
		$this->entity = Inflector::lower($entity);
		$this->data   = $data;
		$this->rules  = $this->explodeRules($rules);
	}

	private function getValue($attribute)
	{
		if ( ! is_null($value = array_get($this->data, $attribute)))
		{
			return $value;
		}
		elseif ( ! is_null($value = array_get($this->files, $attribute)))
		{
			return $value;
		}
	}

	/**
	 * Explode the rules into an array of rules.
	 *
	 * @param  string|array  $rules
	 * @return array
	 */
	private function explodeRules($rules)
	{
		foreach ($rules as $key => &$rule)
		{
			$rule = (is_string($rule)) ? explode('|', $rule) : $rule;
		}

		return $rules;
	}

	public function passes()
	{
		foreach ($this->rules as $attribute => $rules)
		{
			foreach ($rules as $rule)
			{
				$this->validate($attribute, $rule);
			}
		}

		return count($this->messages) === 0;
	}

	protected function validate($attribute, $rule)
	{
		list($rule, $parameters) = $this->parseRule($rule);

		if ($rule == '') {
			return;
		}

		$value = Helpers::arrayGet($this->data, $attribute);

		$validatable = $this->isValidatable($rule, $attribute, $value);

		$method = "validate{$rule}";

		//var_dump($value, $rule, $attribute, $parameters, $method, $this);

		if ($validatable && ! $this->$method($attribute, $value, $parameters, $this))
		{
			$this->addFailure($attribute, $rule, $parameters);
		}
	}

	/**
	 * Extract the rule name and parameters from a rule.
	 *
	 * @param  array|string  $rules
	 * @return array
	 */
	protected function parseRule($rules)
	{
		if (is_array($rules))
		{
			return $this->parseArrayRule($rules);
		}

		return $this->parseStringRule($rules);
	}

	/**
	 * Parse an array based rule.
	 *
	 * @param  array  $rules
	 * @return array
	 */
	protected function parseArrayRule(array $rules)
	{
		return array(Inflector::underCamel(trim(Helpers::arrayGet($rules, 0))), array_slice($rules, 1));
	}

	/**
	 * Parse a string based rule.
	 *
	 * @param  string  $rules
	 * @return array
	 */
	protected function parseStringRule($rules)
	{
		$parameters = [];

		// The format for specifying validation rules and parameters follows an
		// easy {rule}:{parameters} formatting convention. For instance the
		// rule "Max:3" states that the value may only be three letters.
		if (strpos($rules, ':') !== false)
		{
			list($rules, $parameter) = explode(':', $rules, 2);

			$parameters = $this->parseParameters($rules, $parameter);
		}

		return array(Inflector::underCamel(trim($rules)), $parameters);
	}

	/**
	 * Parse a parameter list.
	 *
	 * @param  string  $rule
	 * @param  string  $parameter
	 * @return array
	 */
	protected function parseParameters($rule, $parameter)
	{
		if (strtolower($rule) == 'regex') return array($parameter);

		return str_getcsv($parameter);
	}

	/**
	 * Determine if the attribute is validatable.
	 *
	 * @param  string  $rule
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @return bool
	 */
	protected function isValidatable($rule, $attribute, $value)
	{
		return $this->presentOrRuleIsImplicit($rule, $attribute, $value) &&
               $this->passesOptionalCheck($attribute);
	}

	/**
	 * Determine if the field is present, or the rule implies required.
	 *
	 * @param  string  $rule
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @return bool
	 */
	protected function presentOrRuleIsImplicit($rule, $attribute, $value)
	{
		return $this->validateRequired($attribute, $value) || $this->isImplicit($rule);
	}

	/**
	 * Determine if the attribute passes any optional check.
	 *
	 * @param  string  $attribute
	 * @return bool
	 */
	protected function passesOptionalCheck($attribute)
	{
		if ($this->hasRule($attribute, ['Sometimes']))
		{
			return array_key_exists($attribute, $this->data);
		}

		return true;
	}

	/**
	 * Determine if the given attribute has a rule in the given set.
	 *
	 * @param  string  $attribute
	 * @param  string|array  $rules
	 * @return bool
	 */
	protected function hasRule($attribute, $rules)
	{
		return ! is_null($this->getRule($attribute, $rules));
	}

	/**
	 * Get a rule and its parameters for a given attribute.
	 *
	 * @param  string  $attribute
	 * @param  string|array  $rules
	 * @return array|null
	 */
	protected function getRule($attribute, $rules)
	{
		if ( ! array_key_exists($attribute, $this->rules))
		{
			return;
		}

		$rules = (array) $rules;

		foreach ($this->rules[$attribute] as $rule)
		{
			list($rule, $parameters) = $this->parseRule($rule);

			if (in_array($rule, $rules)) return [$rule, $parameters];
		}
	}

	/**
	 * Determine if a given rule implies the attribute is required.
	 *
	 * @param  string  $rule
	 * @return bool
	 */
	protected function isImplicit($rule)
	{
		return in_array($rule, $this->implicitRules);
	}

	/**
	 * Add a failed rule and error message to the collection.
	 *
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @param  array   $parameters
	 * @return void
	 */
	protected function addFailure($attribute, $rule, $parameters)
	{
		$this->addError($attribute, $rule, $parameters);

		//$this->failedRules[$attribute][$rule] = $parameters;
	}

	/**
	 * Add an error message to the validator's collection of messages.
	 *
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @param  array   $parameters
	 * @return void
	 */
	protected function addError($attribute, $rule, $parameters)
	{
		$message = $this->getMessage($attribute, $rule);

		$message = $this->doReplacements($message, $attribute, $rule, $parameters);

		$this->messages[] = $message;
	}

	/**
	 * Get the validation message for an attribute and rule.
	 *
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @return string
	 */
	protected function getMessage($attribute, $rule)
	{

		$lowerRule = Inflector::camelUnder($rule);

		$message = Lang::get("validation.messages.{$lowerRule}");

		return $message;
	}

	/**
	 * Replace all error message place-holders with actual values.
	 *
	 * @param  string  $message
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @param  array   $parameters
	 * @return string
	 */
	protected function doReplacements($message, $attribute, $rule, $parameters)
	{
		$message = str_replace(':attribute', $this->getAttribute($attribute), $message);

		$message = $this->callReplacer($message, $attribute, $rule, $parameters);

		return $message;

		/*if (isset($this->replacers[snake_case($rule)]))
		{
			$message = $this->callReplacer($message, $attribute, Helpers::($rule), $parameters);
		}
		elseif (method_exists($this, $replacer = "replace{$rule}"))
		{
			$message = $this->$replacer($message, $attribute, $rule, $parameters);
		}

		return $message;*/
	}

	/**
	 * Call a custom validator message replacer.
	 *
	 * @param  string  $message
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @param  array   $parameters
	 * @return string
	 */
	protected function callReplacer($message, $attribute, $rule, $parameters)
	{
		if (method_exists($this, $replacer = "replace{$rule}")) {
			return call_user_func_array([$this, $replacer], func_get_args());
		}

		return $message;

	}

	/**
	 * Replace all place-holders for the date_format rule.
	 *
	 * @param  string  $message
	 * @param  string  $attribute
	 * @param  string  $rule
	 * @param  array   $parameters
	 * @return string
	 */
	protected function replaceDateFormat($message, $attribute, $rule, $parameters)
	{
		return str_replace(':format', $parameters[0], $message);
	}

	/**
	 * Get the displayable name of the attribute.
	 *
	 * @param  string  $attribute
	 * @return string
	 */
	protected function getAttribute($attribute)
	{
		$key = "{$this->entity}.columns_title.{$attribute}";

		return Lang::get($key);
	}

	public function messages()
	{
		if ( ! $this->messages) $this->passes();

		return $this->messages;
	}

	public function errors()
	{
		return $this->messages();
	}

	/**
	 * Require a certain number of parameters to be present.
	 *
	 * @param  int    $count
	 * @param  array  $parameters
	 * @param  string  $rule
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	protected function requireParameterCount($count, $parameters, $rule)
	{
		if (count($parameters) < $count)
		{
			throw new \InvalidArgumentException("Validation rule $rule requires at least $count parameters.");
		}
	}

	/**
	 * Validate that a required attribute exists.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @return bool
	 */
	protected function validateRequired($attribute, $value)
	{
		if (is_null($value))
		{
			return false;
		}
		elseif (is_string($value) && trim($value) === '')
		{
			return false;
		}
		elseif (is_array($value) && count($value) < 1)
		{
			return false;
		}
		elseif ($value instanceof File)
		{
			return (string) $value->getPath() != '';
		}

		return true;
	}

	/**
	 * Validate that an attribute is a valid IP.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @return bool
	 */
	protected function validateIp($attribute, $value)
	{
		return filter_var($value, FILTER_VALIDATE_IP) !== false;
	}

	/**
	 * Validate that an attribute is a valid e-mail address.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @return bool
	 */
	protected function validateEmail($attribute, $value)
	{
		return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
	}

	/**
	 * Validate that an attribute is a valid URL.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @return bool
	 */
	protected function validateUrl($attribute, $value)
	{
		return filter_var($value, FILTER_VALIDATE_URL) !== false;
	}

	/**
	 * Validate that an attribute is numeric.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @return bool
	 */
	protected function validateNumeric($attribute, $value)
	{
		return is_numeric($value);
	}

	/**
	 * Validate that an attribute is an array.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @return bool
	 */
	protected function validateArray($attribute, $value)
	{
		return is_array($value);
	}

	/**
	 * Validate that an attribute is a valid date.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @return bool
	 */
	protected function validateDate($attribute, $value)
	{
		if ($value instanceof DateTime) return true;

		if (strtotime($value) === false) return false;

		$date = date_parse($value);

		return checkdate($date['month'], $date['day'], $date['year']);
	}

	/**
	 * Validate that an attribute matches a date format.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $value
	 * @param  array   $parameters
	 * @return bool
	 */
	protected function validateDateFormat($attribute, $value, $parameters)
	{
		$this->requireParameterCount(1, $parameters, 'date_format');

		$parsed = date_parse_from_format($parameters[0], $value);

		return $parsed['error_count'] === 0 && $parsed['warning_count'] === 0;
	}
}