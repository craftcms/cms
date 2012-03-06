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
		$runner = self::getRunner();
		$args = array('yiic', 'migrate', '--migrationTable='.b()->config->getDbItem('tablePrefix').'_migrations', '--interactive=0');

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

		$runner = self::getRunner();
		$args = array('yiic', 'migrate', 'to', $migrationShortName, '--migrationTable='.b()->config->getDbItem('tablePrefix').'_migrations', '--interactive=0');

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

		$runner = self::getRunner();
		$args = array('yiic', 'migrate', '--migrationTable='.b()->config->getDbItem('tablePrefix').'migrations', 'down', $number, '--interactive=0');

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
		$runner = self::getRunner();
		$args = array('yiic', 'migrate', 'create', $migrationName, '--interactive=0');

		ob_start();
		$runner->run($args);

		return htmlentities(ob_get_clean(), null, b()->charset);
	}

	/**
	 * @access private
	 * @static
	 * @return \CConsoleCommandRunner
	 */
	private static function getRunner()
	{
		$commandPath = b()->path->commandsPath;
		$runner = new \CConsoleCommandRunner();
		$runner->addCommands($commandPath);

		$commandPath = b()->path->frameworkPath.'cli/commands';
		$runner->addCommands($commandPath);

		return $runner;
	}
}
