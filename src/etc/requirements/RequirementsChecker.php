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

	/**
	 *
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
		$info[] = 'Yii v'.craft()->getYiiVersion();
		$info[] =  \CTimestamp::formatDate(craft()->locale->getTimeFormat());;

		return implode(' | ', $info);
	}
}
