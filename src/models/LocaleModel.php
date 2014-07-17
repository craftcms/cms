<?php
namespace Craft;

/**
 * Stores locale info.
 *
 * @package craft.app.models
 */
class LocaleModel extends BaseApplicationComponent
{
	private $_id;
	private $_nameInLanguage;

	/**
	 * Use the ID as the string representation of locales.
	 */
	function __toString()
	{
		return $this->_id;
	}

	/**
	 * Constructor
	 */
	function __construct($id)
	{
		$this->_id = $id;
	}

	public function getId()
	{
		return $this->_id;
	}

	/**
	 * Returns the locale name in a given language.
	 *
	 * @param string|null $targetLocaleId
	 * @return string|null
	 */
	public function getName($targetLocaleId = null)
	{
		// If no language is specified, default to the user's language
		if (!$targetLocaleId)
		{
			$targetLocaleId = craft()->language;
		}

		if (!isset($this->_nameInLanguage) || !array_key_exists($targetLocaleId, $this->_nameInLanguage))
		{
			$localeData = craft()->i18n->getLocaleData($targetLocaleId);

			if ($localeData)
			{
				$name = $localeData->getLocaleDisplayName($this->_id);

				if (!$name)
				{
					// Try grabbling the language and territory separately...
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
