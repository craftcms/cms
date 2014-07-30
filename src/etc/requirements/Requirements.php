<?php
namespace Craft;

/**
 * Class Requirements
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @link      http://buildwithcraft.com
 * @package   craft.app.etc.requirements
 * @since     1.2
 */
class Requirements
{
	/**
	 * @return array
	 */
	public static function getRequirements()
	{
		$requiredMysqlVersion = '@@@requiredMySQLVersion@@@';

		return array(
			new PhpVersionRequirement(),
			new Requirement(
				Craft::t('$_SERVER Variable'),
				($message = static::_checkServerVar()) === '',
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
				extension_loaded('mcrypt'),
				true,
				'<a href="http://www.yiiframework.com/doc/api/CSecurityManager">CSecurityManager</a>',
				Craft::t('<a href="http://php.net/manual/en/book.mcrypt.php">Mcrypt</a> is required.')
			),
			new Requirement(
				Craft::t('GD extension with FreeType support or Imagick extension'),
				extension_loaded('gd') || extension_loaded('imagick'),
				true,
				'<a href="http://buildwithcraft.com">@@@appName@@@</a>',
				'<a href="http://php.net/manual/en/book.image.php">GD</a> or <a href="http://php.net/manual/en/class.imagick.php">Imagick</a> is required.'
			),
			new Requirement(
				Craft::t('MySQL version'),
				version_compare(craft()->db->getServerVersion(), $requiredMysqlVersion, ">="),
				true,
				'<a href="http://buildwithcraft.com">@@@appName@@@</a>',
				Craft::t('MySQL {version} or higher is required to run @@@appName@@@.', array('version' => $requiredMysqlVersion))
			),
			new Requirement(
				Craft::t('MySQL InnoDB support'),
				static::_isInnoDbEnabled(),
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
				'<a href="http://buildwithcraft.com">@@@appName@@@</a>',
				Craft::t('<a href="http://php.net/manual/en/book.pcre.php">PCRE</a> must be compiled to support UTF-8.')
			),
			new Requirement(
				Craft::t('Multibyte String support'),
				(extension_loaded('mbstring') && ini_get('mbstring.func_overload') != 1),
				true,
				'<a href="http://buildwithcraft.com">@@@appName@@@</a>',
				Craft::t('@@@appName@@@ requires the <a href="http://www.php.net/manual/en/book.mbstring.php">Multibyte String extension</a> with <a href="http://php.net/manual/en/mbstring.overload.php">Function Overloading</a> disabled in order to run.')
			),
			new Requirement(
				Craft::t('iconv support'),
				function_exists('iconv'),
				false,
				'<a href="http://buildwithcraft.com">@@@appName@@@</a>',
				Craft::t('@@@appName@@@ requires <a href="http://php.net/manual/en/book.iconv.php">iconv</a> in order to run.')
			),
		);
	}

	/**
	 * @return string
	 */
	private static function _checkServerVar()
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
	 * Checks to see if the MySQL InnoDB storage engine is installed and enabled.
	 *
	 * @return bool
	 */
	private function _isInnoDbEnabled()
	{
		$results = craft()->db->createCommand()->setText('SHOW ENGINES')->queryAll();

		foreach ($results as $result)
		{
			if (strtolower($result['Engine']) == 'innodb' && strtolower($result['Support']) != 'no')
			{
				return true;
			}
		}

		return false;
	}
}

/**
 * Requirement class.
 *
 * @package craft.app.etc.requirements
 */
class Requirement extends \CComponent
{
	/**
	 * @var null|string
	 */
	private $_name;

	/**
	 * @var bool|null
	 */
	private $_condition;

	/**
	 * @var null|string
	 */
	private $_requiredBy;

	/**
	 * @var null|string
	 */
	private $_notes;

	/**
	 * @var bool|null
	 */
	private $_required;

	/**
	 * @var
	 */
	private $_result;

	/**
	 * Constructor
	 *
	 * @param string|null $name
	 * @param bool|null   $condition
	 * @param bool|null   $required
	 * @param string|null $requiredBy
	 * @param string|null $notes
	 *
	 * @return Requirement
	 */
	function __construct($name = null, $condition = null, $required = true, $requiredBy = null, $notes = null)
	{
		$this->_name = $name;
		$this->_condition = $condition;
		$this->_required = $required;
		$this->_requiredBy = $requiredBy;
		$this->_notes = $notes;
	}

	/**
	 * Calculates the result of this requirement.
	 *
	 * @return string
	 */
	protected function calculateResult()
	{
		if ($this->_condition)
		{
			return RequirementResult::Success;
		}
		else if ($this->_required)
		{
			return RequirementResult::Failed;
		}
		else
		{
			return RequirementResult::Warning;
		}
	}

	/**
	 * @return mixed
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * @return string
	 */
	public function getResult()
	{
		if (!isset($this->_result))
		{
			$this->_result = $this->calculateResult();
		}

		return $this->_result;
	}

	/**
	 * @return bool
	 */
	public function getRequired()
	{
		return $this->_required;
	}

	/**
	 * @return void
	 */
	public function getRequiredBy()
	{
		return $this->_requiredBy;
	}

	/**
	 * @return void
	 */
	public function getNotes()
	{
		return $this->_notes;
	}
}

/**
 * PHP version requirement class.
 *
 * @package craft.app.etc.requirements
 */
class PhpVersionRequirement extends Requirement
{
	const REQUIRED_PHP_VERSION = '@@@requiredPHPVersion@@@';

	/**
	 * @return PhpVersionRequirement
	 */
	function __construct()
	{
		parent::__construct(
			Craft::t('PHP Version'),
			null,
			true,
			'<a href="http://buildwithcraft.com">@@@appName@@@</a>'
		);
	}

	/**
	 * @return void
	 */
	public function getNotes()
	{
		if ($this->_isBadPhpVersion())
		{
			return Craft::t('PHP {version} has a known <a href="{url}">security vulnerability</a>. You should probably upgrade.', array(
				'version' => PHP_VERSION,
				'url'     => 'http://arstechnica.com/security/2014/03/php-bug-allowing-site-hijacking-still-menaces-internet-22-months-on'
			));
		}
		else
		{
			return Craft::t('PHP {version} or higher is required.', array(
				'version' => static::REQUIRED_PHP_VERSION,
			));
		}
	}

	/**
	 * Calculates the result of this requirement.
	 *
	 * @return string
	 */
	protected function calculateResult()
	{
		if ($this->_doesMinVersionPass())
		{
			// If it's 5.3 < 5.3.12, or 5.4 < 5.4.2, still issue a warning, due to the PHP hijack bug:
			// http://arstechnica.com/security/2014/03/php-bug-allowing-site-hijacking-still-menaces-internet-22-months-on/
			if ($this->_isBadPhpVersion())
			{
				return RequirementResult::Warning;
			}
			else
			{
				return RequirementResult::Success;
			}
		}
		else
		{
			return RequirementResult::Failed;
		}
	}

	/**
	 * Returns whether this is past the min PHP version.
	 *
	 * @return bool
	 */
	private function _doesMinVersionPass()
	{
		return version_compare(PHP_VERSION, static::REQUIRED_PHP_VERSION, '>=');
	}

	/**
	 * Returns whether this is one of the bad PHP versions.
	 *
	 * @return bool
	 */
	private function _isBadPhpVersion()
	{
		return (
			(version_compare(PHP_VERSION, '5.3', '>=') && version_compare(PHP_VERSION, '5.3.12', '<')) ||
			(version_compare(PHP_VERSION, '5.4', '>=') && version_compare(PHP_VERSION, '5.4.2', '<'))
		);
	}
}
