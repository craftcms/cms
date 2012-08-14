<?php
namespace Blocks;

/**
 * Date functions
 */
class DateVariable
{
	private $dateTimeVariable;

	/**
	 * Constructor
	 */
	function __construct()
	{
		$this->dateTimeVariable = new DateTime();
	}

	/**
	 * Forward any unknown requests to the DateTime variable.
	 * @param string $name
	 * @return string
	 */
	function __get($name)
	{
		return (string)$this->dateTimeVariable->$name;
	}

	/**
	 * Returns a given number of seconds in a more meaningful format.
	 * @param int $seconds
	 * @return string
	 */
	public function secondsToHumanTimeDuration($seconds)
	{
		return DateTimeHelper::secondsToHumanTimeDuration($seconds);
	}

	/**
	 * @param $dateString
	 * @return string
	 */
	public function nice($dateString)
	{
		return DateTimeHelper::nice($dateString);
	}
}
