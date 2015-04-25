<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\debug;

use Craft;
use yii\debug\Panel;

/**
 * Debugger panel that collects and displays application and environment info.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class InfoPanel extends Panel
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function getName()
	{
		return 'Info';
	}

	/**
	 * @inheritdoc
	 */
	public function getSummary()
	{
		return Craft::$app->getView()->render('@app/views/debug/info/summary', ['panel' => $this]);
	}

	/**
	 * @inheritdoc
	 */
	public function getDetail()
	{
		return Craft::$app->getView()->render('@app/views/debug/info/detail', ['panel' => $this]);
	}

	/**
	 * @inheritdoc
	 */
	public function save()
	{
		return [
			'craftVersion' => Craft::$app->version,
			'craftBuild' => Craft::$app->build,
			'craftReleaseDate' => Craft::$app->releaseDate->getTimestamp(),
			'craftEdition' => Craft::$app->getEdition(),
			'packages' => [
				'Yii' => \Yii::getVersion(),
				'Twig' => \Twig_Environment::VERSION,
				'Guzzle' => \GuzzleHttp\Client::VERSION,
				'Imagine' => \Imagine\Gd\Imagine::VERSION,
			],
			'plugins' => $this->_getPlugins(),
			'requirements' => $this->_getRequirementResults(),
			'phpVersion' => PHP_VERSION,
			'phpInfo' => $this->_getPhpInfo(),
		];
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns info about the installed plugins
	 */
	private function _getPlugins()
	{
		$plugins = [];

		foreach (Craft::$app->getPlugins()->getAllPlugins() as $plugin)
		{
			$plugins[] = [
				'name' => $plugin->name,
				'version' => $plugin->version,
				'developer' => $plugin->developer,
				'developerUrl' => $plugin->developerUrl,
			];
		}

		return $plugins;
	}

	/**
	 * Runs the requirements checker and returns its results.
	 */
	private function _getRequirementResults()
	{
		require_once(Craft::$app->getPath()->getAppPath().'/requirements/RequirementsChecker.php');
		$reqCheck = new \RequirementsChecker();
		$reqCheck->checkCraft();
		return $reqCheck->getResult()['requirements'];
	}

	/**
	 * Parses and returns the PHP info.
	 *
	 * @return array
	 */
	private function _getPhpInfo()
	{
		Craft::$app->getConfig()->maxPowerCaptain();

		ob_start();
		phpinfo(-1);
		$phpInfo = ob_get_clean();

		$phpInfo = preg_replace(
			[
				'#^.*<body>(.*)</body>.*$#ms',
				'#<h2>PHP License</h2>.*$#ms',
				'#<h1>Configuration</h1>#',
				"#\r?\n#",
				"#</(h1|h2|h3|tr)>#",
				'# +<#',
				"#[ \t]+#",
				'#&nbsp;#',
				'#  +#',
				'# class=".*?"#',
				'%&#039;%',
				'#<tr>(?:.*?)"src="(?:.*?)=(.*?)" alt="PHP Logo" /></a><h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#',
				'#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#',
				'#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#',
				"# +#",
				'#<tr>#',
				'#</tr>#'
			],
			[
				'$1',
				'',
				'',
				'',
				'</$1>'."\n",
				'<',
				' ',
				' ',
				' ',
				'',
				' ',
				'<h2>PHP Configuration</h2>'."\n".'<tr><td>PHP Version</td><td>$2</td></tr>'."\n".'<tr><td>PHP Egg</td><td>$1</td></tr>',
				'<tr><td>PHP Credits Egg</td><td>$1</td></tr>',
				'<tr><td>Zend Engine</td><td>$2</td></tr>'."\n".'<tr><td>Zend Egg</td><td>$1</td></tr>',
				' ',
				'%S%',
				'%E%'
			],
			$phpInfo
		);

		$sections = explode('<h2>', strip_tags($phpInfo, '<h2><th><td>'));
		unset($sections[0]);

		$phpInfo = [];
		foreach($sections as $section)
		{
			$heading = substr($section, 0, strpos($section, '</h2>'));

			preg_match_all(
				'#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#',
				$section,
				$parts,
				PREG_SET_ORDER
			);

			foreach($parts as $row)
			{
				if (!isset($row[2]))
				{
					continue;
				}
				else if ((!isset($row[3]) || $row[2] == $row[3]))
				{
					$value = $row[2];
				}
				else
				{
					$value = array_slice($row, 2);
				}

				$phpInfo[$heading][$row[1]] = $value;
			}
		}

		return $phpInfo;
	}
}
