<?php

class PathService extends CApplicationComponent implements IPathService
{
	/* Paths */
	public function getBasePath()
	{
		return BLOCKS_BASE_PATH;
	}

	public function getConfigPath()
	{
		return $this->getBasePath().'config/';
	}

	public function getPluginsPath()
	{
		return $this->getBasePath().'plugins/';
	}

	public function getResourcesPath()
	{
		return $this->getAppPath().'resources/';
	}

	public function getAppPath()
	{
		return Blocks::app()->getBasePath().'/';
	}

	public function getFrameworkPath()
	{
		return $this->getAppPath().'framework/';
	}

	public function getRuntimePath()
	{
		return Blocks::app()->getRuntimePath().'/';
	}

	public function getResourceProcessorPath()
	{
		return $this->getAppPath().'business/web/ResourceProcessor.php';
	}

	public function getResourceProcessorUrl()
	{
		return '/index.php/blocks/app/business/web/ResourceProcessor.php';
	}

	public function getCPTemplatePath()
	{
		return $this->getAppPath().'templates/';
	}

	public function getMigrationsPath()
	{
		return $this->getAppPath().'migrations/';
	}

	public function getCommandsPath()
	{
		return $this->getAppPath().'commands/';
	}

	public function getSiteTemplatePath()
	{
		$siteHandle = Blocks::app()->request->getSiteInfo();
		$siteHandle = $siteHandle == null ? 'default' : $siteHandle->handle;

		return $this->getBasePath().'templates/'.$siteHandle.'/';
	}

	public function getTemplatePath()
	{
		if (Blocks::app()->request->getCMSRequestType() == RequestType::Site)
			return $this->getSiteTemplatePath();

		if (($moduleName = Blocks::app()->url->getTemplateMatch()->getModuleName()) !== null)
			return $this->getAppPath().'modules/'.$moduleName.'/templates/';

		return $this->getCPTemplatePath();
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
				$cachePath = $this->getRuntimePath().'cached/'.$siteHandle.'/translated_site_templates/';
				break;

			case RequestType::ControlPanel:
				$cachePath = $this->getRuntimePath().'cached/translated_cp_templates/';

				if (($moduleName = Blocks::app()->url->getTemplateMatch()->getModuleName()) !== null)
					$cachePath .= 'modules/'.$moduleName.'/';
				break;

			default:
				$cachePath = $this->getRuntimePath().'/cached';
		}

		if (!is_dir($cachePath))
			mkdir($cachePath, 0777, true);

		return $cachePath;
	}
}
