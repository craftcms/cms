<?php
namespace Craft;

/**
 *
 */
class Requirement extends \CComponent
{
	private $_name;
	private $_condition;
	private $_requiredBy;
	private $_notes;
	private $_required;
	private $_result;

	/**
	 * Constructor
	 *
	 * @param string|null $name
	 * @param bool|null   $condition
	 * @param bool|null   $required
	 * @param string|null $requiredBy
	 * @param string|null $notes
	 */
	function __construct($name = null, $condition = null, $required = true, $requiredBy = null, $notes = null)
	{
		$this->_name = $name;
		$this->_condition = $condition;
		$this->_required = $required;
		$this->_requiredBy = $requiredBy;
		$this->_notes = $notes;
	}

	/**
	 * Calculates the result of this requirement.
	 *
	 * @access protected
	 * @return string
	 */
	protected function calculateResult()
	{
		if ($this->_condition)
		{
			return RequirementResult::Success;
		}
		else if ($this->_required)
		{
			return RequirementResult::Failed;
		}
		else
		{
			return RequirementResult::Warning;
		}
	}

	/**
	 * @return mixed
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * @return string
	 */
	public function getResult()
	{
		if (!isset($this->_result))
		{
			$this->_result = $this->calculateResult();
		}

		return $this->_result;
	}

	/**
	 * @return bool
	 */
	public function getRequired()
	{
		return $this->_required;
	}

	/**
	 * @return null
	 */
	public function getRequiredBy()
	{
		return $this->_requiredBy;
	}

	/**
	 * @return null
	 */
	public function getNotes()
	{
		return $this->_notes;
	}
}
