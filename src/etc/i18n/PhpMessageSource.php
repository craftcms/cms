<?php
namespace Craft;

/**
 *
 */
class PhpMessageSource extends \CPhpMessageSource
{
	public $forceTranslation = true;

	private $_translations;

	/**
	 * Loads the message translation for the specified language and category.
	 *
	 * @param string $category the message category
	 * @param string $language the target language
	 * @return array the loaded messages
	 */
	protected function loadMessages($category, $language)
	{
		if ($category != 'craft')
		{
			return parent::loadMessages($category, $language);
		}

		if (!isset($this->_translations[$language]))
		{
			$this->_translations[$language] = array();

			$paths[] = craft()->path->getCpTranslationsPath();
			$paths[] = craft()->path->getSiteTranslationsPath();

			foreach ($paths as $path)
			{
				$file = $path.$language.'.php';

				if (IOHelper::fileExists($file))
				{
					$translations = include($file);

					if (is_array($translations))
					{
						$this->_translations[$language] = array_merge($this->_translations[$language], $translations);
					}
				}
			}
		}

		return $this->_translations[$language];
	}
}
