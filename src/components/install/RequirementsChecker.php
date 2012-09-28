<?php
namespace Blocks;

/**
 *
 */
class RequirementsChecker extends \CComponent
{
	private $_requirements;
	private $_result;
	private $_serverInfo;
	private $_errorFolders;

	/**
	 * @access private
	 * @todo Find out what min versions of MySQL and other databases we are going to support.
	 */
	private function init()
	{
		$requiredPhpVersion = blx()->params['requiredPhpVersion'];
		$requiredMysqlVersion = blx()->params['requiredMysqlVersion'];

		$this->_requirements = array(
			new Requirement(
				Blocks::t('PHP Version'),
				version_compare(PHP_VERSION, $requiredPhpVersion, ">="),
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				Blocks::t('PHP {version} or higher is required.', array('version' => $requiredPhpVersion))
			),
			new Requirement(
				Blocks::t('$_SERVER Variable'),
				($message = $this->checkServerVar()) === '',
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				$message
			),
			new Requirement(
				Blocks::t('Reflection extension'),
				class_exists('Reflection', false),
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				''
			),
			new Requirement(
				Blocks::t('PCRE extension'),
				extension_loaded("pcre"),
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				''
			),
			new Requirement(
				'SPL extension',
				extension_loaded("SPL"),
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				''
			),
			new Requirement(
				Blocks::t('DOM extension'),
				class_exists("DOMDocument",false),
				false,
				'<a href="http://www.yiiframework.com/doc/api/CHtmlPurifier">CHtmlPurifier</a>, <a href="http://www.yiiframework.com/doc/api/CWsdlGenerator">CWsdlGenerator</a>',
				''
			),
			new Requirement(
				Blocks::t('PDO extension'),
				extension_loaded('pdo'),
				false,
				Blocks::t('All <a href="http://www.yiiframework.com/doc/api/#system.db">DB-related classes</a>'),
				''
			),
			new Requirement(
				Blocks::t('PDO SQLite extension'),
				extension_loaded('pdo_sqlite'),
				false,
				Blocks::t('All <a href="http://www.yiiframework.com/doc/api/#system.db">DB-related classes</a>'),
				Blocks::t('This is required if you are using SQLite database.')
			),
			new Requirement(
				Blocks::t('PDO MySQL extension'),
				extension_loaded('pdo_mysql'),
				false,
				Blocks::t('All <a href="http://www.yiiframework.com/doc/api/#system.db">DB-related classes</a>'),
				Blocks::t('This is required if you are using MySQL database.')
			),
			new Requirement(
				Blocks::t('PDO PostgreSQL extension'),
				extension_loaded('pdo_pgsql'),
				false,
				Blocks::t('All <a href="http://www.yiiframework.com/doc/api/#system.db">DB-related classes</a>'),
				Blocks::t('This is required if you are using PostgreSQL database.')),
			new Requirement(
				Blocks::t('Memcache extension'),
				extension_loaded("memcache") || extension_loaded("memcached"),
				false,
				'<a href="http://www.yiiframework.com/doc/api/CMemCache">CMemCache</a>',
				extension_loaded("memcached") ? Blocks::t('To use memcached set <a href="http://www.yiiframework.com/doc/api/CMemCache#useMemcached-detail">CMemCache::useMemcached</a> to <code>true</code>.') : Blocks::t('Only required if you plan on using Memcache.')
			),
			new Requirement(
				Blocks::t('APC extension'),
				extension_loaded("apc"),
				false,
				'<a href="http://www.yiiframework.com/doc/api/CApcCache">CApcCache</a>',
				Blocks::t('Only required if you plan on APC for caching.')
			),
			new Requirement(
				Blocks::t('Mcrypt extension'),
				extension_loaded("mcrypt"),
				false,
				'<a href="http://www.yiiframework.com/doc/api/CSecurityManager">CSecurityManager</a>',
				Blocks::t('This is required by encrypt and decrypt methods.')
			),
			new Requirement(
				Blocks::t('SOAP extension'),
				extension_loaded("soap"),
				false,
				'<a href="http://www.yiiframework.com/doc/api/CWebService">CWebService</a>, <a href="http://www.yiiframework.com/doc/api/CWebServiceAction">CWebServiceAction</a>',
				''
			),
			new Requirement(
				Blocks::t('GD extension w/ FreeType support'),
				($message = $this->_checkGD()) === '',
				false,
				'<a href="http://www.yiiframework.com/doc/api/CCaptchaAction">CCaptchaAction</a>, Assets',
				$message
			),
			new Requirement(
				Blocks::t('MySQL version'),
				version_compare(blx()->db->serverVersion, $requiredMysqlVersion, ">="),
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				Blocks::t('MySQL {version} or higher is required to run Blocks.', array('version' => $requiredMysqlVersion))
			),
			new Requirement(
				Blocks::t('Glob'),
				function_exists('glob'),
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				Blocks::t('Your PHP installation does not support the <a href="http://us.php.net/manual/en/function.glob.php">glob</a> function.')
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
			return Blocks::t('$_SERVER does not have {messages}.', array('messages' => implode(', ', $missing)));

		if (!isset($_SERVER["REQUEST_URI"]) && isset($_SERVER["QUERY_STRING"]))
			return Blocks::t('Either $_SERVER["REQUEST_URI"] or $_SERVER["QUERY_STRING"] must exist.');

		if (!isset($_SERVER["PATH_INFO"]) && strpos($_SERVER["PHP_SELF"], $_SERVER["SCRIPT_NAME"]) !== 0)
			return Blocks::t('Unable to determine URL path info. Please make sure $_SERVER["PATH_INFO"] (or $_SERVER["PHP_SELF"] and $_SERVER["SCRIPT_NAME"]) contains proper value.');

		return '';
	}

	/**
	 * @access private
	 * @return string
	 */
	private function _checkGD()
	{
		if (extension_loaded('gd'))
		{
			$gdInfo = gd_info();

			if ($gdInfo['FreeType Support'])
				return '';

			return Blocks::t('GD installed').'<br />'.Blocks::t('FreeType support not installed.');
		}

		return Blocks::t('GD not installed');
	}

	/**
	 * @access private
	 * @return string
	 */
	private function _calculateServerInfo()
	{
		$info[] = '<a href="http://www.blockscms.com/">Blocks</a> v'.Blocks::getVersion().' '.Blocks::t('build').' '.Blocks::getBuild(false);
		$info[] = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '';
		$info[] = 'Yii v'.Blocks::getYiiVersion();
		$info[] =  \CTimestamp::formatDate(blx()->locale->getTimeFormat());;
		return implode(' | ', $info);
	}

	/**
	 * @access private
	 * @return array
	 */
	private function _getWritableFolders()
	{
		$folders = array(
			blx()->path->getRuntimePath(),
		);

		return $folders;
	}

	/**
	 */
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

		$writableFolders = $this->_getWritableFolders();

		$errorFolders = null;

		foreach ($writableFolders as $writableFolder)
		{
			if (!IOHelper::isWritable($writableFolder))
			{
				$errorFolders[] = IOHelper::getRealPath($writableFolder);
				$installResult = InstallStatus::Failure;
			}
		}

		$this->_result = $installResult;
		$this->_serverInfo = $this->_calculateServerInfo();
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
