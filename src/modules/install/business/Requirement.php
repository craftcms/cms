<?php

class Requirement
{
	private $_name;
	private $_condition;
	private $_requiredBy;
	private $_notes;
	private $_required;
	private $_result = RequirementResult::Success;


	function __construct($name, $condition, $required = true, $requiredBy = null, $notes = null)
	{
		$this->_name = $name;
		$this->_condition = $condition;
		$this->_required = $required;
		$this->_requiredBy = $requiredBy;
		$this->_notes = $notes;

		$this->_calculateResult();
	}

	private function _calculateResult()
	{
		if ($this->_required && !$this->_condition)
		{
			$this->_result = RequirementResult::Failed;
		}
		else
		{
			if (!$this->_required && !$this->_condition)
			{
				$this->_result = RequirementResult::Warning;
			}
		}
	}

	public function getName()
	{
		return $this->_name;
	}

	public function getResult()
	{
		return $this->_result;
	}

	public function getRequired()
	{
		return $this->_required;
	}

	public function getRequiredBy()
	{
		return $this->_requiredBy;
	}

	public function getNotes()
	{
		return $this->_notes;
	}
}
