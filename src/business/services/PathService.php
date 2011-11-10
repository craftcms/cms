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
		return $this->getBasePath().'config'.DIRECTORY_SEPARATOR;
	}

	public function getPluginsPath()
	{
		return $this->getBasePath().'plugins'.DIRECTORY_SEPARATOR;
	}

	public function getResourcesPath()
	{
		return $this->getAppPath().'resources'.DIRECTORY_SEPARATOR;
	}

	public function getAppPath()
	{
		return Blocks::app()->getBasePath().DIRECTORY_SEPARATOR;
	}

	public function getFrameworkPath()
	{
		return $this->getAppPath().'framework'.DIRECTORY_SEPARATOR;
	}

	public function getRuntimePath()
	{
		return Blocks::app()->getRuntimePath().DIRECTORY_SEPARATOR;
	}

	public function getResourceProcessorPath()
	{
		return $this->getAppPath().'business'.DIRECTORY_SEPARATOR.'web'.DIRECTORY_SEPARATOR.'ResourceProcessor.php';
	}

	public function getResourceProcessorUrl()
	{
		return '/index.php/blocks/app/business/web/ResourceProcessor.php';
	}

	public function getCPTemplatePath()
	{
		return $this->getAppPath().'templates'.DIRECTORY_SEPARATOR;
	}

	public function getSiteTemplatePath()
	{
		$siteHandle = Blocks::app()->request->getSiteInfo();
		$siteHandle = $siteHandle == null ? 'default' : $siteHandle->handle;

		return $this->getBasePath().'templates'.DIRECTORY_SEPARATOR.$siteHandle.DIRECTORY_SEPARATOR;
	}

	public function getTemplatePath()
	{
		if (Blocks::app()->request->getCMSRequestType() == RequestType::Site)
			return $this->getSiteTemplatePath();

		if (($moduleName = Blocks::app()->url->getTemplateMatch()->getModuleName()) !== null)
			return $this->getAppPath().'modules'.DIRECTORY_SEPARATOR.$moduleName.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR;

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
				$cachePath = $this->getRuntimePath().'cached'.DIRECTORY_SEPARATOR.$siteHandle.DIRECTORY_SEPARATOR.'translated_site_templates'.DIRECTORY_SEPARATOR;
				break;

			case RequestType::ControlPanel:
				$cachePath = $this->getRuntimePath().'cached'.DIRECTORY_SEPARATOR.'translated_cp_templates'.DIRECTORY_SEPARATOR;

				if (($moduleName = Blocks::app()->url->getTemplateMatch()->getModuleName()) !== null)
					$cachePath .= 'modules'.DIRECTORY_SEPARATOR.$moduleName.DIRECTORY_SEPARATOR;
				break;

			default:
				$cachePath = $this->getRuntimePath().DIRECTORY_SEPARATOR.'cached';
		}

		if (!is_dir($cachePath))
			mkdir($cachePath, 0777, true);

		return $cachePath;
	}
}
