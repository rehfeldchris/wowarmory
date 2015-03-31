<?php

namespace WowArmory\Parser\Json;


/*
 * Purpose: To validate the structure of complicated arrays, so that you don't need to make tedious calls to isset() and is_array()
 
 * Syntax:
 * 		$v = new SimpleArrayStructureValidator();
 * 		$v->addRule('foo', 's');
 * 		$valid = $v->validate($someVar);
 * Is like doing:
 * 		$valid = is_array($someVar) && array_key_exists('foo', $someVar) && is_string($someVar['foo']);
  *
 * The star '*' in a rule like is a wildcard, causing the remaining path to be required for all child elements.
  * For example:
 * 		$v = new SimpleArrayStructureValidator();
 * 		$v->addRule('foo.*', 'i');
 * 		$someVar = array('foo' => array(array(1), array(1)));
 * 		$valid = $v->validate($someVar); // true
 */
class SimpleArrayStructureValidator
{
	protected $ruleList = [];
	protected $failingRules = [];

	protected $types = [
		's'   => 'is_string'
		, 'i' => 'is_int'
		, 'o' => 'is_object'
		, 'a' => 'is_array'
		, 'n' => 'is_null'
		, 'b' => 'is_bool'
		, 'f' => 'is_float'
		, 'd' => 'ctype_digit'
		, '?' => [__CLASS__, 'wildcardType']
	];


	public function addRule($path, $typeValidator = '?')
	{
		if (is_string($path)) {
			$path = $this->stringPathToArrayPath($path);
		}
		if (!$this->validateRule($path)) {
			throw new \InvalidArgumentException("bad rule");
		}
		if (!$callable = $this->toCallable($typeValidator)) {
			throw new \InvalidArgumentException("unknown type validator");
		}
		$path[] = $callable;
		$this->ruleList[] = $path;

		return $this;
	}

	public function addRules($rules)
	{
		foreach ($rules as $rule) {
			$this->addRule($rule[0], $rule[1]);
		}

		return $this;
	}

	public function validate($dataStructure)
	{
		foreach ($this->ruleList as $rule) {
			if (!$this->ruleApplies($rule, $dataStructure)) {
				$type = array_pop($rule);
				$ruleStr = join('.', $rule);
				$this->failingRules[] = $ruleStr . ' => ' . (is_string($type) ? $type : 'user func');

				return false;
			}
		}

		return true;
	}

	protected function validateRule($rule)
	{
		return is_array($rule)
		&& count($rule) === count(array_filter($rule, 'is_string'));
	}

	protected function toCallable($typeValidator)
	{
		if (is_string($typeValidator) && isset($this->types[ $typeValidator ])) {
			return $this->types[ $typeValidator ];
		}
		if (is_callable($typeValidator)) {
			return $typeValidator;
		}

		return null;
	}

	protected function stringPathToArrayPath($path)
	{
		return explode('.', $path);
	}

	protected function ruleApplies($rule, $dataStructure)
	{
		$ruleComponent = array_shift($rule);
		if (count($rule) === 0) {
			// the last entry in the rule array is always a callable type validation func
			return $this->checkTypeMatches($dataStructure, $ruleComponent);
		}
		if (!is_array($dataStructure)) {
			return false;
		}
		if ($ruleComponent === '*') {
			foreach ($dataStructure as $childStructure) {
				if (!$this->ruleApplies($rule, $childStructure)) {
					return false;
				}
			}

			return true;
		}
		if (!array_key_exists($ruleComponent, $dataStructure)) {
			return false;
		}

		return $this->ruleApplies($rule, $dataStructure[ $ruleComponent ]);
	}


	protected function checkTypeMatches($dataStructure, $callable)
	{
		return call_user_func($callable, $dataStructure);
	}

	public static function wildcardType()
	{
		return true;
	}

	public static function validateOnce($dataStructure, $rules)
	{
		$v = new self();
		$v->addRules($rules);

		return $v->validate($dataStructure);
	}

	public function getFailingRules()
	{
		return $this->failingRules;
	}


}