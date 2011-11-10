<?php

class BlocksApp extends CWebApplication
{
	private $_viewPath;
	private $_layoutPath;
	private $_dbInstalled = null;

	//Blocks::app()->attachEventHandler('onBeginRequest', array($this, 'blar'));

	public function init()
	{
		// run the resource processor if necessary.
		if (Blocks::app()->request->getRequestType() == 'GET')
		{
			if (Blocks::app()->request->getQuery('resourcePath', null) !== null)
			{
				$resourceProcessor = new ResourceProcessor();
				$resourceProcessor->processResourceRequest();
			}
		}

		parent::init();
	}

	public function blar()
	{
		if ('127.0.0.1' === $_SERVER['REMOTE_ADDR'])
		{
			//Blocks::app()->catchAllRequest = null;
		}
	}

	public function run()
	{
		$this->validateConfig();

		if (Blocks::app()->url->getTemplateMatch() !== null || Blocks::app()->request->getParam('c', null) !== null)
			$this->catchAllRequest = array('blocks/index');

		parent::run();
	}

	private function validateConfig()
	{
		$pathInfo = Blocks::app()->request->getPathInfo();

		if (strpos($pathInfo, '/install') !== false)
			return;

		if (strpos($pathInfo, '/error') !== false)
			return;

		$messages = array();

		$databaseServerName = Blocks::app()->config->getDatabaseServerName();
		$databaseAuthName = Blocks::app()->config->getDatabaseAuthName();
		$databaseAuthPassword = Blocks::app()->config->getDatabaseAuthPassword();
		$databaseName = Blocks::app()->config->getDatabaseName();
		$databaseType = Blocks::app()->config->getDatabaseType();
		$databasePort = Blocks::app()->config->getDatabasePort();
		$databaseTablePrefix = Blocks::app()->config->getDatabaseTablePrefix();
		$databaseCharset = Blocks::app()->config->getDatabaseCharset();
		$databaseCollation = Blocks::app()->config->getDatabaseCollation();

		if (StringHelper::IsNullOrEmpty($databaseServerName))
			$messages[] = 'The database server name is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databaseAuthName))
			$messages[] = 'The database user name is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databaseAuthPassword))
			$messages[] = 'The database password is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databaseName))
			$messages[] = 'The database name is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databasePort))
			$messages[] = 'The database port is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databaseTablePrefix))
			$messages[] = 'The database table prefix is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databaseCharset))
			$messages[] = 'The database charset is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databaseCollation))
			$messages[] = 'The database collation is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databaseType))
			$messages[] = 'The database type is not set in your db config file.';
		else
		{
			if (!in_array($databaseType, Blocks::app()->config->getDatabaseSupportedTypes()))
				$messages[] = 'Blocks does not support the database type you have set in your db config file.';
		}

		if (!empty($messages))
			throw new BlocksException(implode(PHP_EOL, $messages));

		try
		{
			$connection = Blocks::app()->db;
			if (!$connection)
				$messages[] = 'There is a problem connecting to the database with the credentials supplied in your db config file.';
		}
		catch(Exception $e)
		{
			$messages[] = 'There is a problem connecting to the database with the credentials supplied in your db config file.';
		}

		if (!empty($messages))
			throw new BlocksException(implode(PHP_EOL, $messages));

		if (!$this->isDbInstalled())
		{
			if (Blocks::app()->request->getCMSRequestType() == RequestType::Site)
					throw new BlocksHttpException(404, 'Page not found.');
			else
			{
				$pathInfo = Blocks::app()->request->getPathSegments();
				if (!$pathInfo || $pathInfo[0] !== 'install')
					Blocks::app()->request->redirect('/admin.php/install');
			}
		}
	}

	public function isDbInstalled()
	{
		if ($this->_dbInstalled == null)
		{
			// Check to see if the prefix_info table exists.  If not, we assume it's a fresh installation.
			$infoTable = Blocks::app()->db->schema->getTable(Blocks::app()->config->getDatabaseTablePrefix().'_info');

			$this->_dbInstalled = $infoTable === null ? false : true;
		}

		return $this->_dbInstalled;
	}

	// we can't use setViewPath() because our view path depends on the request type, which is initialized after web application.
	// so we override getViewPath();
	public function getViewPath()
	{
		if ($this->_viewPath !== null)
			return $this->_viewPath;
		else
		{
			if (get_class($this->request) == 'BlocksHttpRequest')
			{
				$requestType = $this->request->getCMSRequestType();
				if ($requestType == RequestType::Site)
				{
					$viewPath = str_replace('\\', '/', realpath(Blocks::app()->path->getSiteTemplatePath()).'/');
				}
				else
				{
					$pathInfo = Blocks::app()->request->getPathSegments();
					if ($pathInfo && ($module = $this->getModule($pathInfo[0])) !== null)
					{
						$viewPath = $module->getViewPath();
					}
					else
					{
						$viewPath = str_replace('\\', '/', realpath(Blocks::app()->path->getCPTemplatePath()).'/');
					}
				}
			}
			else
			{
				$viewPath = BLOCKS_BASE_PATH.'templates/';
			}

			$this->_viewPath = $viewPath;
			return $this->_viewPath;
		}
	}

	public function getLayoutPath()
	{
		if ($this->_layoutPath !==null)
			return $this->_layoutPath;
		else
			return $this->_layoutPath = $this->getViewPath().'layouts';
	}
}
