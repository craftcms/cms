<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\i18n;

use Craft;
use yii\base\Object;
use yii\i18n\Formatter;

/**
 * Stores locale info.
 *
 * @property string $displayName The locale’s display name.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Locale extends Object
{
	// Properties
	// =========================================================================

	/**
	 * @var The locale ID.
	 */
	public $id;

	/**
	 * @var The locale's formatter.
	 */
	private $_formatter;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor.
	 *
	 * @param int   $id     The locale ID.
	 * @param array $config Name-value pairs that will be used to initialize the object properties.
	 */
	public function __construct($id, $config = [])
	{
		$this->id = $id;
		parent::__construct($config);
	}

	/**
	 * Use the ID as the string representation of locales.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->id;
	}

	/**
	 * Returns the locale name in a given language.
	 *
	 * @param string|null $inLocale
	 * @return string
	 */
	public function getDisplayName($inLocale = null)
	{
		// If no target locale is specified, default to this locale
		if (!$inLocale)
		{
			$inLocale = $this->id;
		}

		if (extension_loaded('intl'))
		{
			return \Locale::getDisplayName($this->id, $inLocale);
		}
		else if ($this->id === 'en')
		{
			return 'English';
		}
		else
		{
			return $this->id;
		}
	}

	/**
	 * Returns a [[Formatter]] for this locale.
	 *
	 * @return Formatter A formatter for this locale.
	 */
	public function getFormatter()
	{
		if ($this->_formatter === null)
		{
			$this->_formatter = new Formatter([
				'locale' => $this->id
			]);
		}

		return $this->_formatter;
	}

	/**
	 * Returns the "AM" name for this locale.
	 *
	 * @return string The "AM" name.
	 */
	public function getAMName()
	{
		return $this->getFormatter()->asDate(new DateTime('00:00'), 'a');
	}

	/**
	 * Returns the "PM" name for this locale.
	 *
	 * @return string The "PM" name.
	 */
	public function getPMName()
	{
		return $this->getFormatter()->asDate(new DateTime('12:00'), 'a');
	}
}
