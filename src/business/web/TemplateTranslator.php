<?php

/*class TemplateTranslator
{
	public function translate($templatePath, $cache = true)
	{
		if (StringHelper::IsNullOrEmpty($templatePath))
			throw new BlocksException('Template path cannot be empty.');

		$templatePath = str_replace('\\', '/', $templatePath);
		$templateFile = Blocks::app()->file->set($templatePath, false);

		if (!$templateFile->getExists())
			throw new BlocksException('Cannot find the template at '.$templatePath);

		$templateContents = $templateFile->getContents();
		// magic translation
		$tranlatedContents = $templateContents;

		if ($cache)
			if (!$this->cacheTemplate($templatePath, $tranlatedContents))
				throw new BlocksException('There was a problem caching the template for '.$templatePath);

		return $tranlatedContents ? true : false;
	}

	// TODO: Caching will move to a separate Blocks Cache Manager.
	private function cacheTemplate($origTemplatePath, $translatedContents)
	{
		return Blocks::app()->templateCache->add($origTemplatePath, $translatedContents, 0);
	}
}*/
