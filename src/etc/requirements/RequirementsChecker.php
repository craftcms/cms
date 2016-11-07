<?php
namespace Craft;

/**
 * Class RequirementsChecker
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.requirements
 * @since     1.0
 */
class RequirementsChecker extends \CComponent
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_requirements;

	/**
	 * @var
	 */
	private $_result;

	/**
	 * @var
	 */
	private $_serverInfo;

	// Public Methods
	// =========================================================================

	/**
	 * @return null
	 */
	public function run()
	{
		$this->_requirements = Requirements::getRequirements();

		$installResult = InstallStatus::Success;

		foreach ($this->_requirements as $requirement)
		{
			if ($requirement->getResult() == RequirementResult::Failed)
			{
				$installResult = InstallStatus::Failed;
				break;
			}
			else if ($requirement->getResult() == RequirementResult::Warning)
			{
				$installResult = InstallStatus::Warning;
			}
		}

		$this->_result = $installResult;
		$this->_serverInfo = $this->_calculateServerInfo();
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
	 * @return mixed
	 */
	public function getRequirements()
	{
		return $this->_requirements;
	}

	// Private Methods
	// =========================================================================

	/**
	 * @return string
	 */
	private function _calculateServerInfo()
	{
		$info[] = '<a href="http://craftcms.com/">Craft CMS</a> ' .
			Craft::t('{version} build {build}', array(
				'version' => CRAFT_VERSION,
				'build'   => CRAFT_BUILD
			));

		$info[] = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '';
		$info[] = 'Yii v'.craft()->getYiiVersion();
		$info[] =  \CTimestamp::formatDate(craft()->locale->getTimeFormat());

		return implode(' | ', $info);
	}
}
