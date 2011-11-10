<?php

class RequirementsChecker
{
	private $_requirements;
	private $_installResult;
	private $_serverInfo;
	private $_errorFolders = null;

	private function init()
	{
		$dbConfigPath = Blocks::app()->path->getConfigPath().'db.php';
		$blocksConfigPath = Blocks::app()->path->getConfigPath().'blocks.php';

		$this->_requirements = array(
			new Requirement(
				'PHP Version',
				version_compare(Blocks::app()->config->getLocalPHPVersion(), BLOCKS_MIN_PHP_VERSION, ">="),
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				'PHP '.BLOCKS_MIN_PHP_VERSION.' or higher is required.'),
			new Requirement(
				'$_SERVER Variable',
				($message = $this->checkServerVar()) === '',
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				$message),
			new Requirement(
				'Reflection extension',
				class_exists('Reflection', false),
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				''),
			new Requirement(
				'PCRE extension',
				extension_loaded("pcre"),
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				''),
			new Requirement(
				'SPL extension',
				extension_loaded("SPL"),
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				''),
			new Requirement(
				'DOM extension',
				class_exists("DOMDocument",false),
				false,
				'<a href="http://www.yiiframework.com/doc/api/CHtmlPurifier">CHtmlPurifier</a>, <a href="http://www.yiiframework.com/doc/api/CWsdlGenerator">CWsdlGenerator</a>',
				''),
			new Requirement(
				'PDO extension',
				extension_loaded('pdo'),
				false,
				'All <a href="http://www.yiiframework.com/doc/api/#system.db">DB-related classes</a>',
				''),
			new Requirement(
				'PDO SQLite extension',
				extension_loaded('pdo_sqlite'),
				false,
				'All <a href="http://www.yiiframework.com/doc/api/#system.db">DB-related classes</a>',
				'This is required if you are using SQLite database.'),
			new Requirement(
				'PDO MySQL extension',
				extension_loaded('pdo_mysql'),
				false,
				'All <a href="http://www.yiiframework.com/doc/api/#system.db">DB-related classes</a>',
				'This is required if you are using MySQL database.'),
			new Requirement(
				'PDO PostgreSQL extension',
				extension_loaded('pdo_pgsql'),
				false,
				'All <a href="http://www.yiiframework.com/doc/api/#system.db">DB-related classes</a>',
				'This is required if you are using PostgreSQL database.'),
			new Requirement(
				'Memcache extension',
				extension_loaded("memcache") || extension_loaded("memcached"),
				false,
				'<a href="http://www.yiiframework.com/doc/api/CMemCache">CMemCache</a>',
				extension_loaded("memcached") ? 'To use memcached set <a href="http://www.yiiframework.com/doc/api/CMemCache#useMemcached-detail">CMemCache::useMemcached</a> to <code>true</code>.' : 'Only required if you plan on using Memcache.'),
			new Requirement(
				'APC extension',
				extension_loaded("apc"),
				false,
				'<a href="http://www.yiiframework.com/doc/api/CApcCache">CApcCache</a>',
				'Only required if you plan on APC for caching.'),
			new Requirement(
				'Mcrypt extension',
				extension_loaded("mcrypt"),
				false,
				'<a href="http://www.yiiframework.com/doc/api/CSecurityManager">CSecurityManager</a>',
				'This is required by encrypt and decrypt methods.'),
			new Requirement(
				'SOAP extension',
				extension_loaded("soap"),
				false,
				'<a href="http://www.yiiframework.com/doc/api/CWebService">CWebService</a>, <a href="http://www.yiiframework.com/doc/api/CWebServiceAction">CWebServiceAction</a>',
				''),
			new Requirement(
				'GD extension w/ FreeType support',
				($message = $this->checkGD()) === '',
				false,
				'<a href="http://www.yiiframework.com/doc/api/CCaptchaAction">CCaptchaAction</a>, Assets',
				$message),
			new Requirement(
				'Database Config Server Name',
				Blocks::app()->config->getDatabaseServerName() !== '',
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				'Please set your database server name in the database config file at '.$dbConfigPath),
			new Requirement(
				'Database Config User Name',
				Blocks::app()->config->getDatabaseAuthName() !== '',
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				'Please set your database user name in the database config file at '.$dbConfigPath),
			new Requirement(
				'Database Config User Password',
				Blocks::app()->config->getDatabaseAuthPassword() !== '',
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				'Please set your database user password in the database config file at '.$dbConfigPath),
			new Requirement(
				'Database Config Database Name',
				Blocks::app()->config->getDatabaseName() !== '',
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				'Please set your database name in the database config file at '.$dbConfigPath),
			new Requirement(
				'Site License Key Name',
				Blocks::app()->config->getSiteLicenseKey() !== '',
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				'Please set your license key in the site config file at '.$blocksConfigPath),
		);

		$connection = true;

		try
		{
			Blocks::app()->db;
		}
		catch (Exception $e)
		{
			$connection = false;
			$message = $e->getMessage();
		}

		if (!$connection)
		{
			$this->_requirements[] = new Requirement(
				'Database Connection',
				false,
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				'Cannot connect to the database with the current settings in the database config file at '.realpath(Blocks::app()->path->getConfigPath()).DIRECTORY_SEPARATOR.'db.php.<br /><br />Message:<br />'.$message);
		}
		else
		{
			/* TODO: Find out what min versions of MySQL and other databases we are going to support. */
			$this->_requirements[] = new Requirement(
			'MySQL version',
			version_compare(Blocks::app()->config->getDatabaseVersion(), BLOCKS_MIN_MYSQL_VERSION, ">="),
			true,
			'<a href="http://www.blockscms.com">Blocks</a>',
			'MySQL '.BLOCKS_MIN_MYSQL_VERSION.' or higher is required to run Blocks.');
		}
	}

	private function checkServerVar()
	{
		$vars = array('HTTP_HOST', 'SERVER_NAME', 'SERVER_PORT', 'SCRIPT_NAME', 'SCRIPT_FILENAME', 'PHP_SELF', 'HTTP_ACCEPT', 'HTTP_USER_AGENT');
		$missing = array();

		foreach($vars as $var)
		{
			if (!isset($_SERVER[$var]))
				$missing[] = $var;
		}

		if (!empty($missing))
			return '$_SERVER does not have '.implode(', ', $missing);

		if (!isset($_SERVER["REQUEST_URI"]) && isset($_SERVER["QUERY_STRING"]))
			return 'Either $_SERVER["REQUEST_URI"] or $_SERVER["QUERY_STRING"] must exist.';

		if (!isset($_SERVER["PATH_INFO"]) && strpos($_SERVER["PHP_SELF"], $_SERVER["SCRIPT_NAME"]) !== 0)
			return 'Unable to determine URL path info. Please make sure $_SERVER["PATH_INFO"] (or $_SERVER["PHP_SELF"] and $_SERVER["SCRIPT_NAME"]) contains proper value.';

		return '';
	}

	private function checkGD()
	{
		if (extension_loaded('gd'))
		{
			$gdInfo = gd_info();

			if ($gdInfo['FreeType Support'])
				return '';

			return 'GD installed<br />FreeType support not installed';
		}

		return 'GD not installed';
	}

	private function calculateServerInfo()
	{
		$info[] = '<a href="http://www.blockscms.com/">Blocks</a> v'.Blocks::getVersion().'.'.Blocks::getBuildNumber();
		$info[] = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '';
		$info[] = 'Yii v'.Blocks::getYiiVersion();
		$info[] = @strftime('%Y-%m-%d %H:%M', time());

		return implode(' | ', $info);
	}

	private function getWritableFolders()
	{
		$folders = array(
			Blocks::app()->file->set(Blocks::getPathOfAlias('base.runtime').DIRECTORY_SEPARATOR, false),
			//Blocks::app()->file->set(Blocks::getPathOfAlias('application.runtime.cached').DIRECTORY_SEPARATOR, false),
		);

		return $folders;
	}

	public function run()
	{

		$this->init();
		$installResult = InstallStatus::Success;

		foreach ($this->_requirements as $requirement)
		{
			if ($requirement->getResult() == RequirementResult::Failed)
			{
				$installResult = InstallStatus::Failure;
				break;
			}
			else if ($requirement->getResult() == RequirementResult::Warning)
				$installResult = InstallStatus::Warning;
		}

		$writableFolders = $this->getWritableFolders();
		$errorFolders = null;
		foreach($writableFolders as $writableFolder)
		{
			if (!$writableFolder->getWriteable())
			{
				$errorFolders[] = $writableFolder->getRealPath();
				$installResult = InstallStatus::Failure;
			}
		}

		$this->_installResult = $installResult;
		$this->_serverInfo = $this->calculateServerInfo();
		$this->_errorFolders = $errorFolders;
	}

	public function getInstallResult()
	{
		return $this->_installResult;
	}

	public function getServerInfo()
	{
		return $this->_serverInfo;
	}

	public function getErrorFolders()
	{
		return $this->_errorFolders;
	}

	public function getRequirements()
	{
		return $this->_requirements;
	}
}
