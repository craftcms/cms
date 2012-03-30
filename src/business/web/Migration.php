<?php
namespace Blocks;

/**
 *
 */
class Migration
{
	/**
	 * @static
	 * @return string
	 */
	public static function runToTop()
	{
		$runner = self::_getRunner();
		$args = array(b()->path->consolePath.'yiic', 'migrate', '--migrationTable=migrations', '--interactive=0');

		ob_start();
		$runner->run($args);

		return htmlentities(ob_get_clean(), null, b()->charset);
	}

	/**
	 * @static
	 * @param $migrationName
	 * @return string
	 */
	public static function run($migrationName)
	{
		// migration names always start with timestamp m'yymmdd_hhmmss'
		$migrationShortName = substr(b()->file->set($migrationName, false)->fileName, 1, 13);

		$runner = self::_getRunner();
		$args = array(b()->path->consolePath.'yiic', 'migrate', 'to', $migrationShortName, '--migrationTable=migrations', '--interactive=0');

		ob_start();
		$runner->run($args);

		return htmlentities(ob_get_clean(), null, b()->charset);
	}

	/**
	 * @static
	 * @param $number
	 * @return string
	 */
	public static function revert($number)
	{
		if (!isset($number))
			$number = 1;

		$runner = self::_getRunner();
		$args = array(b()->path->consolePath.'yiic', 'migrate', '--migrationTable=migrations', 'down', $number, '--interactive=0');

		ob_start();
		$runner->run($args);

		return htmlentities(ob_get_clean(), null, b()->charset);
	}

	/**
	 * @static
	 * @param $migrationName
	 * @return string
	 */
	public static function create($migrationName)
	{
		$migrationName = str_replace(' ', '_', $migrationName);
		$runner = self::_getRunner();
		$args = array(b()->path->consolePath.'yiic', 'migrate', 'create', $migrationName, '--interactive=0');

		ob_start();
		$runner->run($args);

		return htmlentities(ob_get_clean(), null, b()->charset);
	}

	/**
	 * @access private
	 * @static
	 * @return ConsoleCommandRunner
	 */
	private static function _getRunner()
	{
		$commandPath = rtrim(b()->path->commandsPath, '/');
		$runner = new ConsoleCommandRunner();
		$runner->addCommands($commandPath);

		return $runner;
	}
}
