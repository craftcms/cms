<?php

/**
 *
 */
class Requirement
{
	private $_name;
	private $_condition;
	private $_requiredBy;
	private $_notes;
	private $_required;
	private $_result = RequirementResult::Success;

	/**
	 * @access public
	 *
	 * @param      $name
	 * @param      $condition
	 * @param bool $required
	 * @param null $requiredBy
	 * @param null $notes
	 */
	function __construct($name, $condition, $required = true, $requiredBy = null, $notes = null)
	{
		$this->_name = $name;
		$this->_condition = $condition;
		$this->_required = $required;
		$this->_requiredBy = $requiredBy;
		$this->_notes = $notes;

		$this->_calculateResult();
	}

	/**
	 * @access private
	 */
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

	/**
	 * @access public
	 *
	 * @return mixed
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * @access public
	 *
	 * @return string
	 */
	public function getResult()
	{
		return $this->_result;
	}

	/**
	 * @access public
	 *
	 * @return bool
	 */
	public function getRequired()
	{
		return $this->_required;
	}

	/**
	 * @access public
	 *
	 * @return null
	 */
	public function getRequiredBy()
	{
		return $this->_requiredBy;
	}

	/**
	 * @access public
	 *
	 * @return null
	 */
	public function getNotes()
	{
		return $this->_notes;
	}
}
