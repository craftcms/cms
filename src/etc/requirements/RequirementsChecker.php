<?php
namespace Craft;

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
	 *
	 */
	public function init()
	{
		$requiredPhpVersion = craft()->params['requiredPhpVersion'];
		$requiredMysqlVersion = craft()->params['requiredMysqlVersion'];

		$this->_requirements = array(
			new Requirement(
				Craft::t('PHP Version'),
				version_compare(PHP_VERSION, $requiredPhpVersion, ">="),
				true,
				'<a href="http://buildwithcraft.com">@@@appName@@@</a>',
				Craft::t('PHP {version} or higher is required.', array('version' => $requiredPhpVersion))
			),
			new Requirement(
				Craft::t('$_SERVER Variable'),
				($message = $this->_checkServerVar()) === '',
				true,
				'<a href="http://buildwithcraft.com">@@@appName@@@</a>',
				$message
			),
			new Requirement(
				Craft::t('Reflection extension'),
				class_exists('Reflection', false),
				true,
				'<a href="http://buildwithcraft.com">@@@appName@@@</a>',
				'The <a href="http://php.net/manual/en/class.reflectionextension.php">ReflectionExtension</a> is required.'
			),
			new Requirement(
				Craft::t('PCRE extension'),
				extension_loaded("pcre"),
				true,
				'<a href="http://buildwithcraft.com">@@@appName@@@</a>',
				'<a href="http://php.net/manual/en/book.pcre.php">PCRE</a> is required.'
			),
			new Requirement(
				'SPL extension',
				extension_loaded("SPL"),
				true,
				'<a href="http://buildwithcraft.com">@@@appName@@@</a>',
				'<a href="http://php.net/manual/en/book.spl.php">SPL</a> is required.'
			),
			new Requirement(
				Craft::t('PDO extension'),
				extension_loaded('pdo'),
				true,
				Craft::t('All <a href="http://www.yiiframework.com/doc/api/#system.db">DB-related classes</a>'),
				'<a href="http://php.net/manual/en/book.pdo.php">PDO</a> is required.'
			),
			new Requirement(
				Craft::t('PDO MySQL extension'),
				extension_loaded('pdo_mysql'),
				true,
				Craft::t('All <a href="http://www.yiiframework.com/doc/api/#system.db">DB-related classes</a>'),
				Craft::t('The <http://php.net/manual/en/ref.pdo-mysql.php>PDO MySQL</a> driver is required if you are using a MySQL database.')
			),
			new Requirement(
				Craft::t('Mcrypt extension'),
				extension_loaded("mcrypt"),
				false,
				'<a href="http://www.yiiframework.com/doc/api/CSecurityManager">CSecurityManager</a>',
				Craft::t('<a href="http://php.net/manual/en/book.mcrypt.php">Mcrypt</a> is required.')
			),
			new Requirement(
				Craft::t('GD extension with FreeType support'),
				extension_loaded('gd'),
				true,
				'Assets',
				'<a href="http://php.net/manual/en/book.image.php">GD</a> is required.'
			),
			new Requirement(
				Craft::t('MySQL version'),
				version_compare(craft()->db->serverVersion, $requiredMysqlVersion, ">="),
				true,
				'<a href="http://buildwithcraft.com">@@@appName@@@</a>',
				Craft::t('MySQL {version} or higher is required to run @@@appName@@@.', array('version' => $requiredMysqlVersion))
			),
			new Requirement(
				Craft::t('MySQL InnoDB support'),
				craft()->db->getSchema()->isInnoDbEnabled(),
				true,
				'<a href="http://buildwithcraft.com">@@@appName@@@</a>',
				Craft::t('@@@appName@@@ requires the MySQL InnoDB storage engine to run.')
			),
			new Requirement(
				Craft::t('SSL support'),
				extension_loaded('openssl'),
				true,
				'<a href="http://buildwithcraft.com">@@@appName@@@</a>',
				Craft::t('@@@appName@@@ requires <a href="http://php.net/manual/en/book.openssl.php">OpenSSL</a> in order to run.')
			),
			new Requirement(
				Craft::t('cURL support'),
				extension_loaded('curl'),
				true,
				'<a href="http://buildwithcraft.com">@@@appName@@@</a>',
				Craft::t('@@@appName@@@ requires <a href="http://php.net/manual/en/book.curl.php">cURL</a> in order to run.')
			),
			new Requirement(
				Craft::t('crypt() with CRYPT_BLOWFISH enabled'),
				true,
				function_exists('crypt') && defined('CRYPT_BLOWFISH') && CRYPT_BLOWFISH,
				'<a href="http://www.yiiframework.com/doc/api/1.1/CPasswordHelper">CPasswordHelper</a>',
				Craft::t('@@@appName@@@ requires the <a href="http://php.net/manual/en/function.crypt.php">crypt()</a> function with CRYPT_BLOWFISH enabled for secure password storage.')
			),
			new Requirement(
				Craft::t('PCRE UTF-8 support'),
				preg_match('/./u', 'Ü') === 1,
				true,
				Craft::t('<a href="http://php.net/manual/en/book.pcre.php">PCRE</a> must be compiled to support UTF-8.')
			),
			new Requirement(
				Craft::t('Multibyte String support'),
				(extension_loaded('mbstring') && ini_get('mbstring.func_overload') != 1),
				true,
				Craft::t('@@@appName@@@ requires the <a href="http://www.php.net/manual/en/book.mbstring.php">Multibyte String extension</a> with <a href="http://php.net/manual/en/mbstring.overload.php">Function Overloading</a> disabled in order to run.')
			),
			new Requirement(
				Craft::t('iconv support'),
				function_exists('iconv'),
				true,
				Craft::t('@@@appName@@@ requires <a href="http://php.net/manual/en/book.iconv.php">iconv</a> in order to run.')
			)
		);
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
				{
					$installResult = InstallStatus::Warning;
				}
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

	/**
	 * @access private
	 * @return string
	 */
	private function _checkServerVar()
	{
		$vars = array('HTTP_HOST', 'SERVER_NAME', 'SERVER_PORT', 'SCRIPT_NAME', 'SCRIPT_FILENAME', 'PHP_SELF', 'HTTP_ACCEPT', 'HTTP_USER_AGENT');
		$missing = array();

		foreach($vars as $var)
		{
			if (!isset($_SERVER[$var]))
			{
				$missing[] = $var;
			}
		}

		if (!empty($missing))
		{
			return Craft::t('$_SERVER does not have {messages}.', array('messages' => implode(', ', $missing)));
		}

		if (!isset($_SERVER["REQUEST_URI"]) && isset($_SERVER["QUERY_STRING"]))
		{
			return Craft::t('Either $_SERVER["REQUEST_URI"] or $_SERVER["QUERY_STRING"] must exist.');
		}

		if (!isset($_SERVER["PATH_INFO"]) && strpos($_SERVER["PHP_SELF"], $_SERVER["SCRIPT_NAME"]) !== 0)
		{
			return Craft::t('Unable to determine URL path info. Please make sure $_SERVER["PATH_INFO"] (or $_SERVER["PHP_SELF"] and $_SERVER["SCRIPT_NAME"]) contains proper value.');
		}

		return '';
	}

	/**
	 * @access private
	 * @return string
	 */
	private function _calculateServerInfo()
	{
		$info[] = '<a href="http://buildwithcraft.com/">@@@appName@@@</a> ' .
			Craft::t('{version} build {build}', array(
				'version' => CRAFT_VERSION,
				'build'   => CRAFT_BUILD
			));

		$info[] = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '';
		$info[] = 'Yii v'.Craft::getYiiVersion();
		$info[] =  \CTimestamp::formatDate(craft()->locale->getTimeFormat());;

		return implode(' | ', $info);
	}

	/**
	 * @access private
	 * @return array
	 */
	private function _getWritableFolders()
	{
		$folders = array(
			craft()->path->getRuntimePath(),
			craft()->path->getStoragePath(),
			craft()->path->getConfigPath(),
		);

		return $folders;
	}
}
