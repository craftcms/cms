<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\requirements;

use Craft;
use craft\app\dates\DateTime;
use craft\app\enums\InstallStatus;
use craft\app\enums\RequirementResult;
use yii\base\Object;

/**
 * Class RequirementsChecker
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RequirementsChecker extends Object
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
		$info[] = '<a href="http://buildwithcraft.com/">Craft</a> ' .
			Craft::t('app', '{version} build {build}', [
				'version' => Craft::$app->version,
				'build'   => Craft::$app->build
			]);

		$info[] = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '';
		$info[] = 'Yii v'.Craft::$app->getYiiVersion();

		return implode(' | ', $info);
	}
}
