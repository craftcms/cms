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
		$requiredPhpVersion = b()->params['requiredPhpVersion'];
		$requiredMysqlVersion = b()->params['requiredMysqlVersion'];

		$this->_requirements = array(
			new Requirement(
				'PHP Version',
				version_compare(PHP_VERSION, $requiredPhpVersion, ">="),
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				'PHP '.$requiredPhpVersion.' or higher is required.'
			),
			new Requirement(
				'$_SERVER Variable',
				($message = $this->checkServerVar()) === '',
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				$message
			),
			new Requirement(
				'Reflection extension',
				class_exists('Reflection', false),
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				''
			),
			new Requirement(
				'PCRE extension',
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
				'DOM extension',
				class_exists("DOMDocument",false),
				false,
				'<a href="http://www.yiiframework.com/doc/api/CHtmlPurifier">CHtmlPurifier</a>, <a href="http://www.yiiframework.com/doc/api/CWsdlGenerator">CWsdlGenerator</a>',
				''
			),
			new Requirement(
				'PDO extension',
				extension_loaded('pdo'),
				false,
				'All <a href="http://www.yiiframework.com/doc/api/#system.db">DB-related classes</a>',
				''
			),
			new Requirement(
				'PDO SQLite extension',
				extension_loaded('pdo_sqlite'),
				false,
				'All <a href="http://www.yiiframework.com/doc/api/#system.db">DB-related classes</a>',
				'This is required if you are using SQLite database.'
			),
			new Requirement(
				'PDO MySQL extension',
				extension_loaded('pdo_mysql'),
				false,
				'All <a href="http://www.yiiframework.com/doc/api/#system.db">DB-related classes</a>',
				'This is required if you are using MySQL database.'
			),
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
				extension_loaded("memcached") ? 'To use memcached set <a href="http://www.yiiframework.com/doc/api/CMemCache#useMemcached-detail">CMemCache::useMemcached</a> to <code>true</code>.' : 'Only required if you plan on using Memcache.'
			),
			new Requirement(
				'APC extension',
				extension_loaded("apc"),
				false,
				'<a href="http://www.yiiframework.com/doc/api/CApcCache">CApcCache</a>',
				'Only required if you plan on APC for caching.'
			),
			new Requirement(
				'Mcrypt extension',
				extension_loaded("mcrypt"),
				false,
				'<a href="http://www.yiiframework.com/doc/api/CSecurityManager">CSecurityManager</a>',
				'This is required by encrypt and decrypt methods.'
			),
			new Requirement(
				'SOAP extension',
				extension_loaded("soap"),
				false,
				'<a href="http://www.yiiframework.com/doc/api/CWebService">CWebService</a>, <a href="http://www.yiiframework.com/doc/api/CWebServiceAction">CWebServiceAction</a>',
				''
			),
			new Requirement(
				'GD extension w/ FreeType support',
				($message = $this->_checkGD()) === '',
				false,
				'<a href="http://www.yiiframework.com/doc/api/CCaptchaAction">CCaptchaAction</a>, Assets',
				$message
			),
			new Requirement(
				'MySQL version',
				version_compare(b()->db->serverVersion, $requiredMysqlVersion, ">="),
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				'MySQL '.$requiredMysqlVersion.' or higher is required to run Blocks.'
			),
			new Requirement(
				'Glob',
				function_exists('glob'),
				true,
				'<a href="http://www.blockscms.com">Blocks</a>',
				'Your PHP installation does not support the <a href="http://us.php.net/manual/en/function.glob.php">glob</a> function.'
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
	private function _checkGD()
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
	private function _calculateServerInfo()
	{
		$info[] = '<a href="http://www.blockscms.com/">Blocks</a> v'.Blocks::getVersion(false).' build '.Blocks::getBuild(false);
		$info[] = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '';
		$info[] = 'Yii v'.Blocks::getYiiVersion();
		$info[] = @strftime('%Y-%m-%d %H:%M', time());

		return implode(' | ', $info);
	}

	/**
	 * @access private
	 * @return array
	 */
	private function _getWritableFolders()
	{
		$folders = array(
			b()->file->set(b()->path->runtimePath, false),
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
			if ($requirement->result == RequirementResult::Failed)
			{
				$installResult = InstallStatus::Failure;
				break;
			}
			else if ($requirement->result == RequirementResult::Warning)
				$installResult = InstallStatus::Warning;
		}

		$writableFolders = $this->_getWritableFolders();

		$errorFolders = null;

		foreach ($writableFolders as $writableFolder)
		{
			if (!$writableFolder->writable)
			{
				$errorFolders[] = $writableFolder->realPath;
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
