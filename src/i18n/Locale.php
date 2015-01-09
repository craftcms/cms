<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\i18n;

use Craft;
use yii\base\Object;

/**
 * Stores locale info.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Locale extends Object
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_id;

	/**
	 * @var
	 */
	private $_nameInLanguage;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor
	 *
	 * @param $id
	 *
	 * @return Locale
	 */
	public function __construct($id)
	{
		$this->_id = $id;
	}

	/**
	 * Use the ID as the string representation of locales.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->_id;
	}

	public function getId()
	{
		return $this->_id;
	}

	/**
	 * Returns the locale name in a given language.
	 *
	 * @param string|null $targetLocaleId
	 *
	 * @return string|null
	 */
	public function getName($targetLocaleId = null)
	{
		// If no language is specified, default to the user's language
		if (!$targetLocaleId)
		{
			$targetLocaleId = Craft::$app->language;
		}

		if (!isset($this->_nameInLanguage) || !array_key_exists($targetLocaleId, $this->_nameInLanguage))
		{
			$localeData = Craft::$app->i18n->getLocaleData($targetLocaleId);

			if ($localeData)
			{
				$name = $localeData->getLocaleDisplayName($this->_id);

				if (!$name)
				{
					// Try grabbing the language and territory separately...
					$name = $localeData->getLanguage($this->_id);

					if ($name)
					{
						$territory = $localeData->getTerritory($this->_id);

						if ($territory)
						{
							$name .= ' - '.$territory;
						}
					}
					else if ($targetLocaleId != 'en')
					{
						// Fall back on English
						return $this->getName('en');
					}
					else
					{
						// Use the locale ID as a last result
						return $this->_id;
					}
				}
			}
			else
			{
				$name = null;
			}

			$this->_nameInLanguage[$targetLocaleId] = $name;
		}

		return $this->_nameInLanguage[$targetLocaleId];
	}

	/**
	 * Returns the locale name in its own language.
	 *
	 * @return string|false
	 */
	public function getNativeName()
	{
		return $this->getName($this->_id);
	}
}
