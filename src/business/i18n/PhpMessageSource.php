<?php
namespace Blocks;

/**
 *
 */
class PhpMessageSource extends \CPhpMessageSource
{
	private $_blocks_files = array();

	public function init()
	{
		$this->forceTranslation = true;
		$this->basePath = blx()->path->getTranslationsPath();
		parent::init();
	}

	/**
	 * @param string $category
	 * @param string $language
	 * @return string
	 */
	protected function getMessageFile($category, $language)
	{
		if ($category !== 'blocks')
			return parent::getMessageFile($category, $language);

		if (!isset($this->_blocks_files[$language]))
			$this->_blocks_files[$language] = blx()->path->getTranslationsPath().$language.'.php';

		return $this->_blocks_files[$language];
	}
}
