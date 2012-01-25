<?php

/**
 *
 */
class bRequirementsChecker extends CComponent
{
	private $_requirements;
	private $_result;
	private $_serverInfo;
	private $_errorFolders = null;

	/**
	 * @access private
	 * @todo Find out what min versions of MySQL and other databases we are going to support.
	 */
	private function init()
	{
		$dbConfigPath = Blocks::app()->path->configPath.'db.php';
		$blocksConfigPath = Blocks::app()->path->configPath.'blocks.php';

		$requiredPhpVersion = Blocks::app()->params['requiredPhpVersion'];
		$requiredMysqlVersion = Blocks::app()->params['requiredMysqlVersion'];

		$this->_requirements = array(
			new bRequirement(
				'PHP Version',
				version_compare(PHP_VERSION, $requiredPhpVersion, ">="),
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				'PHP '.$requiredPhpVersion.' or higher is required.'
			),
			new bRequirement(
				'$_SERVER Variable',
				($message = $this->checkServerVar()) === '',
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				$message
			),
			new bRequirement(
				'Reflection extension',
				class_exists('Reflection', false),
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				''
			),
			new bRequirement(
				'PCRE extension',
				extension_loaded("pcre"),
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				''
			),
			new bRequirement(
				'SPL extension',
				extension_loaded("SPL"),
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				''
			),
			new bRequirement(
				'DOM extension',
				class_exists("DOMDocument",false),
				false,
				'<a href="http://www.yiiframework.com/doc/api/CHtmlPurifier">CHtmlPurifier</a>, <a href="http://www.yiiframework.com/doc/api/CWsdlGenerator">CWsdlGenerator</a>',
				''
			),
			new bRequirement(
				'PDO extension',
				extension_loaded('pdo'),
				false,
				'All <a href="http://www.yiiframework.com/doc/api/#system.db">DB-related classes</a>',
				''
			),
			new bRequirement(
				'PDO SQLite extension',
				extension_loaded('pdo_sqlite'),
				false,
				'All <a href="http://www.yiiframework.com/doc/api/#system.db">DB-related classes</a>',
				'This is required if you are using SQLite database.'
			),
			new bRequirement(
				'PDO MySQL extension',
				extension_loaded('pdo_mysql'),
				false,
				'All <a href="http://www.yiiframework.com/doc/api/#system.db">DB-related classes</a>',
				'This is required if you are using MySQL database.'
			),
			new bRequirement(
				'PDO PostgreSQL extension',
				extension_loaded('pdo_pgsql'),
				false,
				'All <a href="http://www.yiiframework.com/doc/api/#system.db">DB-related classes</a>',
				'This is required if you are using PostgreSQL database.'),
			new bRequirement(
				'Memcache extension',
				extension_loaded("memcache") || extension_loaded("memcached"),
				false,
				'<a href="http://www.yiiframework.com/doc/api/CMemCache">CMemCache</a>',
				extension_loaded("memcached") ? 'To use memcached set <a href="http://www.yiiframework.com/doc/api/CMemCache#useMemcached-detail">CMemCache::useMemcached</a> to <code>true</code>.' : 'Only required if you plan on using Memcache.'
			),
			new bRequirement(
				'APC extension',
				extension_loaded("apc"),
				false,
				'<a href="http://www.yiiframework.com/doc/api/CApcCache">CApcCache</a>',
				'Only required if you plan on APC for caching.'
			),
			new bRequirement(
				'Mcrypt extension',
				extension_loaded("mcrypt"),
				false,
				'<a href="http://www.yiiframework.com/doc/api/CSecurityManager">CSecurityManager</a>',
				'This is required by encrypt and decrypt methods.'
			),
			new bRequirement(
				'SOAP extension',
				extension_loaded("soap"),
				false,
				'<a href="http://www.yiiframework.com/doc/api/CWebService">CWebService</a>, <a href="http://www.yiiframework.com/doc/api/CWebServiceAction">CWebServiceAction</a>',
				''
			),
			new bRequirement(
				'GD extension w/ FreeType support',
				($message = $this->checkGD()) === '',
				false,
				'<a href="http://www.yiiframework.com/doc/api/CCaptchaAction">CCaptchaAction</a>, Assets',
				$message
			),
			new bRequirement(
				'MySQL version',
				version_compare(Blocks::app()->db->serverVersion, $requiredMysqlVersion, ">="),
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				'MySQL '.$requiredMysqlVersion.' or higher is required to run Blocks.'
			),
		);
	}

	/**
	 * @access private
	 * @return string
	 */
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

	/**
	 * @access private
	 * @return string
	 */
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

	/**
	 * @access private
	 * @return string
	 */
	private function calculateServerInfo()
	{
		$info[] = '<a href="http://www.blockscms.com/">Blocks</a> v'.Blocks::getVersion(false).'.'.Blocks::getBuild(false);
		$info[] = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '';
		$info[] = 'Yii v'.Blocks::getYiiVersion();
		$info[] = @strftime('%Y-%m-%d %H:%M', time());

		return implode(' | ', $info);
	}

	/**
	 * @access private
	 * @return array
	 */
	private function getWritableFolders()
	{
		$folders = array(
			Blocks::app()->file->set(Blocks::getPathOfAlias('base.runtime').DIRECTORY_SEPARATOR, false),
			//Blocks::app()->file->set(Blocks::getPathOfAlias('application.runtime.cached').DIRECTORY_SEPARATOR, false),
		);

		return $folders;
	}

	/**
	 */
	public function run()
	{
		$this->init();
		$installResult = bInstallStatus::Success;

		foreach ($this->_requirements as $requirement)
		{
			if ($requirement->result == bRequirementResult::Failed)
			{
				$installResult = bInstallStatus::Failure;
				break;
			}
			else if ($requirement->result == bRequirementResult::Warning)
				$installResult = bInstallStatus::Warning;
		}

		$writableFolders = $this->getWritableFolders();
		$errorFolders = null;
		foreach($writableFolders as $writableFolder)
		{
			if (!$writableFolder->writeable)
			{
				$errorFolders[] = $writableFolder->realPath;
				$installResult = bInstallStatus::Failure;
			}
		}

		$this->_result = $installResult;
		$this->_serverInfo = $this->calculateServerInfo();
		$this->_errorFolders = $errorFolders;
	}

	/**
	 * @return mixed
	 */
	public function getResult()
	{
		return $this->_result;
	}

	/**
	 * @return mixed
	 */
	public function getServerInfo()
	{
		return $this->_serverInfo;
	}

	/**
	 * @return null
	 */
	public function getErrorFolders()
	{
		return $this->_errorFolders;
	}

	/**
	 * @return mixed
	 */
	public function getRequirements()
	{
		return $this->_requirements;
	}
}
