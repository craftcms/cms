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
		return $this->normalizeDirectorySeparators($this->basePath.'config/');
	}

	public function getBlocksConfigPath()
	{
		return $this->normalizeDirectorySeparators($this->configPath.'blocks.php');
	}

	public function getPluginsPath()
	{
		return $this->normalizeDirectorySeparators($this->basePath.'plugins/');
	}

	public function getResourcesPath()
	{
		return $this->normalizeDirectorySeparators($this->appPath.'resources/');
	}

	public function getAppPath()
	{
		return $this->normalizeDirectorySeparators(Blocks::app()->basePath.'/');
	}

	public function getFrameworkPath()
	{
		return $this->normalizeDirectorySeparators($this->appPath.'framework/');
	}

	public function getRuntimePath()
	{
		return $this->normalizeDirectorySeparators(Blocks::app()->runtimePath.'/');
	}

	public function getCPTemplatePath()
	{
		return $this->normalizeDirectorySeparators($this->appPath.'templates/');
	}

	public function getMigrationsPath()
	{
		return $this->normalizeDirectorySeparators($this->appPath.'migrations/');
	}

	public function getCommandsPath()
	{
		return $this->normalizeDirectorySeparators($this->appPath.'commands/');
	}

	public function getSiteTemplatePath()
	{
		$siteHandle = Blocks::app()->site->currentSiteByUrl;
		$siteHandle = $siteHandle == null ? 'default' : $siteHandle->handle;

		return $this->normalizeDirectorySeparators($this->basePath.'templates/'.$siteHandle.'/');
	}

	public function getTemplatePath()
	{
		if (Blocks::app()->request->cmsRequestType == RequestType::Site)
			return $this->siteTemplatePath;

		if (Blocks::app()->request->cmsRequestType == RequestType::Controller)
			return null;

		if (($module = Blocks::app()->urlManager->currentModule) !== null)
			return $this->appPath.'modules/'.$module->Id.'/templates/';

		return $this->normalizeDirectorySeparators($this->cpTemplatePath);
	}

	public function getTemplateCachePath()
	{
		$cachePath = null;

		$requestType = Blocks::app()->request->cmsRequestType;
		switch ($requestType)
		{
			case RequestType::Site:
				$siteHandle = Blocks::app()->site->currentSiteByUrl;
				$siteHandle = $siteHandle == null ? 'default' : $siteHandle->handle;
				$cachePath = $this->runtimePath.'parsed_templates/sites/'.$siteHandle.'/';
				break;

			case RequestType::ControlPanel:
				$cachePath = $this->runtimePath.'parsed_templates/cp/';

				if (($module = Blocks::app()->urlManager->currentModule) !== null)
					$cachePath .= 'modules/'.$module->Id.'/';
				break;

			case RequestType::Controller:
				return null;

			default:
				$cachePath = $this->runtimePath.'/parsed_templates/';
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
