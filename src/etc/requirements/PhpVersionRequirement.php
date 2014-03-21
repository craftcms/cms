<?php
namespace Craft;

/**
 *
 */
class PhpVersionRequirement extends Requirement
{
	const REQUIRED_PHP_VERSION = '@@@requiredPHPVersion@@@';

	/**
	 * @param      $name
	 * @param      $condition
	 * @param bool $required
	 * @param null $requiredBy
	 * @param null $notes
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
	 * @return null
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
	 * @access protected
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
	 * @access private
	 * @return bool
	 */
	private function _doesMinVersionPass()
	{
		return version_compare(PHP_VERSION, static::REQUIRED_PHP_VERSION, '>=');
	}

	/**
	 * Returns whether this is one of the bad PHP versions.
	 *
	 * @access private
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
