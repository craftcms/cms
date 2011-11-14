<?php

class PathService extends CApplicationComponent implements IPathService
{
	/* Paths */
	public function getBasePath()
	{
		return $this->normalizeDirectorySeparators(BLOCKS_BASE_PATH);
	}

	public function getConfigPath()
	{
		return $this->normalizeDirectorySeparators($this->getBasePath().'config/');
	}

	public function getPluginsPath()
	{
		return $this->normalizeDirectorySeparators($this->getBasePath().'plugins/');
	}

	public function getResourcesPath()
	{
		return $this->normalizeDirectorySeparators($this->getAppPath().'resources/');
	}

	public function getAppPath()
	{
		return $this->normalizeDirectorySeparators(Blocks::app()->getBasePath().'/');
	}

	public function getFrameworkPath()
	{
		return $this->normalizeDirectorySeparators($this->getAppPath().'framework/');
	}

	public function getRuntimePath()
	{
		return $this->normalizeDirectorySeparators(Blocks::app()->getRuntimePath().'/');
	}

	public function getResourceProcessorPath()
	{
		return $this->normalizeDirectorySeparators($this->getAppPath().'business/web/ResourceProcessor.php');
	}

	public function getResourceProcessorUrl()
	{
		return '/index.php/blocks/app/business/web/ResourceProcessor.php';
	}

	public function getCPTemplatePath()
	{
		return $this->normalizeDirectorySeparators($this->getAppPath().'templates/');
	}

	public function getMigrationsPath()
	{
		return $this->normalizeDirectorySeparators($this->getAppPath().'migrations/');
	}

	public function getCommandsPath()
	{
		return $this->normalizeDirectorySeparators($this->getAppPath().'commands/');
	}

	public function getSiteTemplatePath()
	{
		$siteHandle = Blocks::app()->request->getSiteInfo();
		$siteHandle = $siteHandle == null ? 'default' : $siteHandle->handle;

		return $this->normalizeDirectorySeparators($this->getBasePath().'templates/'.$siteHandle.'/');
	}

	public function getTemplatePath()
	{
		if (Blocks::app()->request->getCMSRequestType() == RequestType::Site)
			return $this->getSiteTemplatePath();

		if (($moduleName = Blocks::app()->url->getTemplateMatch()->getModuleName()) !== null)
			return $this->getAppPath().'modules/'.$moduleName.'/templates/';

		return $this->normalizeDirectorySeparators($this->getCPTemplatePath());
	}

	public function getTemplateCachePath()
	{
		$cachePath = null;

		$requestType = Blocks::app()->request->getCMSRequestType();
		switch ($requestType)
		{
			case RequestType::Site:
				$siteHandle = Blocks::app()->request->getSiteInfo();
				$siteHandle = $siteHandle == null ? 'default' : $siteHandle->handle;
				$cachePath = $this->getRuntimePath().'parsed_templates/sites/'.$siteHandle.'/';
				break;

			case RequestType::ControlPanel:
				$cachePath = $this->getRuntimePath().'parsed_templates/cp/';

				if (($moduleName = Blocks::app()->url->getTemplateMatch()->getModuleName()) !== null)
					$cachePath .= 'modules/'.$moduleName.'/';
				break;

			default:
				$cachePath = $this->getRuntimePath().'/parsed_templates/';
		}

		if (!is_dir($cachePath))
			mkdir($cachePath, 0777, true);

		return $this->normalizeDirectorySeparators($cachePath);
	}

	public function normalizeDirectorySeparators($path)
	{
		return str_replace('\\', '/', $path);
	}
}
