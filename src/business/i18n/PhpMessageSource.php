<?php
namespace Blocks;

/**
 *
 */
class PhpMessageSource extends \CPhpMessageSource
{
	public function init()
	{
		$this->forceTranslation = true;
		$this->basePath = blx()->path->getTranslationsPath();
		parent::init();
	}

	protected function getMessageFile($category, $language)
	{
		if ($category !== 'blocks')
			return parent::getMessageFile($category, $language);

		if (!isset($this->_files[$category][$language]))
		{
			if (($pos = strpos($category, '.')) !== false)
			{
				$moduleClass = substr($category,0,$pos);
				$moduleCategory = substr($category,$pos+1);
				$class=new ReflectionClass($moduleClass);
				$this->_files[$category][$language]=dirname($class->getFileName()).DIRECTORY_SEPARATOR.'messages'.DIRECTORY_SEPARATOR.$language.DIRECTORY_SEPARATOR.$moduleCategory.'.php';
			}
			else
				$this->_files[$category][$language]=$this->basePath.DIRECTORY_SEPARATOR.$language.DIRECTORY_SEPARATOR.$category.'.php';
		}

		return $this->_files[$category][$language];
	}
}
