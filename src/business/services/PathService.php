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

	public function getBlocksConfigPath()
	{
		return $this->normalizeDirectorySeparators($this->getConfigPath().'blocks.php');
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
		$siteHandle = Blocks::app()->site->getCurrentSiteByUrl();
		$siteHandle = $siteHandle == null ? 'default' : $siteHandle->handle;

		return $this->normalizeDirectorySeparators($this->getBasePath().'templates/'.$siteHandle.'/');
	}

	public function getTemplatePath()
	{
		if (Blocks::app()->request->type == RequestType::Site)
			return $this->getSiteTemplatePath();

		if (Blocks::app()->request->type == RequestType::Action)
			return null;

		if (($module = Blocks::app()->urlManager->getCurrentModule()) !== null)
			return $this->getAppPath().'modules/'.$module->getId().'/templates/';

		return $this->normalizeDirectorySeparators($this->getCPTemplatePath());
	}

	public function getTemplateCachePath()
	{
		$cachePath = null;

		$requestType = Blocks::app()->request->type;
		switch ($requestType)
		{
			case RequestType::Site:
				$siteHandle = Blocks::app()->site->getCurrentSiteByUrl();
				$siteHandle = $siteHandle == null ? 'default' : $siteHandle->handle;
				$cachePath = $this->getRuntimePath().'parsed_templates/sites/'.$siteHandle.'/';
				break;

			case RequestType::CP:
				$cachePath = $this->getRuntimePath().'parsed_templates/cp/';

				if (($module = Blocks::app()->urlManager->getCurrentModule()) !== null)
					$cachePath .= 'modules/'.$module->getId().'/';
				break;

			case RequestType::Action:
				return null;

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
