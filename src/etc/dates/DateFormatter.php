<?php
namespace Craft;

/**
 * DateFormatter class with functions for date/time-pickers.
 */
class DateFormatter extends \CDateFormatter
{
	private $_datepickerCLocaleFormat;
	private $_datepickerJsFormat;
	private $_datepickerPhpFormat;

	private $_timepickerCLocaleFormat;
	private $_timepickerPhpFormat;

	/**
	 * Returns the jQuery UI Datepicker date format.
	 *
	 * @return string
	 */
	public function getDatepickerJsFormat()
	{
		if (!isset($this->_datepickerJsFormat))
		{
			$format = $this->_getDatepickerCLocaleFormat();
			$tokens = $this->parseFormat($format);

			foreach ($tokens as &$token)
			{
				if (is_array($token))
				{
					$token = $this->_getDatepickerJsToken($token[1]);
				}
			}

			$this->_datepickerJsFormat = implode('', $tokens);
		}

		return $this->_datepickerJsFormat;
	}

	/**
	 * Returns the PHP date format for datepickers.
	 *
	 * @return string
	 */
	public function getDatepickerPhpFormat()
	{
		if (!isset($this->_datepickerPhpFormat))
		{
			$format = $this->_getDatepickerCLocaleFormat();
			$tokens = $this->parseFormat($format);

			foreach ($tokens as &$token)
			{
				if (is_array($token))
				{
					$token = $this->_getDatepickerPhpToken($token[1]);
				}
			}

			$this->_datepickerPhpFormat = implode('', $tokens);
		}

		return $this->_datepickerPhpFormat;
	}

	/**
	 * Returns the PHP time format for timepickers.
	 *
	 * @return string
	 */
	public function getTimepickerPhpFormat()
	{
		if (!isset($this->_timepickerPhpFormat))
		{
			$format = $this->_locale->getTimeFormat('short');
			$tokens = $this->parseFormat($format);

			foreach ($tokens as &$token)
			{
				if (is_array($token))
				{
					$token = $this->_getTimepickerPhpToken($token[1]);
				}
			}

			$this->_timepickerPhpFormat = implode('', $tokens);
		}

		return $this->_timepickerPhpFormat;
	}

	/**
	 * Add support for DateTime objects.
	 *
	 * @param string|DateTime $timestamp
	 * @param string $dateWidth
	 * @param string $timeWidth
	 * @return string
	 */
	public function formatDateTime($timestamp, $dateWidth = 'medium', $timeWidth = 'medium')
	{
		if ($timestamp instanceof \DateTime)
		{
			$timestamp = $timestamp->getTimestamp();
		}

		return parent::formatDateTime($timestamp, $dateWidth, $timeWidth);
	}

	/**
	 * Returns the date format used by the datepicker.
	 * Similar to 'short' except we want to use 4 digit years instead of 2.
	 *
	 * @return string
	 */
	private function _getDatepickerCLocaleFormat()
	{
		if (!isset($this->_datepickerCLocaleFormat))
		{
			$this->_datepickerCLocaleFormat = $this->_locale->getDateFormat('short');

			// Swap 2-digit years with 4.
			$this->_datepickerCLocaleFormat = str_replace('yy', 'y', $this->_datepickerCLocaleFormat);
		}

		return $this->_datepickerCLocaleFormat;
	}

	/**
	 * Converts CLocale time format tokens to jQuery UI Datepicker date format tokens.
	 *
	 * @access private
	 * @param string $token
	 * @return string
	 * @see http://api.jqueryui.com/datepicker/#utility-formatDate
	 */
	private function _getDatepickerJsToken($token)
	{
		switch ($token)
		{
			// Day of the month without leading zeros
			case 'd':
				return 'd';

			// Day of the month with leading zeros
			case 'dd':
				return 'dd';

			// Month without leading zeros
			case 'M':
				return 'm';

			// Month with leading zeros
			case 'MM':
				return 'mm';

			// Four-digit year
			case 'y':
				return 'yy';

			// Two-digit year
			case 'yy':
				return 'y';
		}
	}

	/**
	 * Converts CLocale date format tokens to PHP date() date format tokens.
	 *
	 * @access private
	 * @param string $token
	 * @return string
	 * @see http://php.net/manual/en/function.date.php
	 */
	private function _getDatepickerPhpToken($token)
	{
		switch ($token)
		{
			// Day of the month without leading zeros
			case 'd':
				return 'j';

			// Day of the month with leading zeros
			case 'dd':
				return 'd';

			// Month without leading zeros
			case 'M':
				return 'n';

			// Month with leading zeros
			case 'MM':
				return 'm';

			// Four-digit year
			case 'y':
				return 'Y';

			// Two-digit year
			case 'yy':
				return 'y';
		}
	}

	/**
	 * Converts CLocale time format tokens to PHP date() time format tokens.
	 *
	 * @access private
	 * @param string $token
	 * @return string
	 * @see http://php.net/manual/en/function.date.php
	 */
	private function _getTimepickerPhpToken($token)
	{
		switch ($token)
		{
			// AM/PM
			case 'a':
				return 'A';

			// 12-hour format of an hour without leading zeros
			case 'h':
				return 'g';

			// 24-hour format of an hour without leading zeros
			case 'H':
				return 'G';

			// 12-hour format of an hour with leading zeros
			case 'hh':
				return 'h';

			// 24-hour format of an hour with leading zeros
			case 'HH':
				return 'H';

			// Minutes with leading zeros
			case 'mm':
				return 'i';

			// Seconds with leading zeros
			case 'ss':
				return 's';
		}
	}
}
