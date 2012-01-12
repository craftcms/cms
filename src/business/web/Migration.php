<?php

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
		$args = array('yiic', 'migrate', '--migrationTable='.Blocks::app()->config->databaseTablePrefix.'_migrations', '--interactive=0');

		ob_start();
		$runner->run($args);

		return htmlentities(ob_get_clean(), null, Blocks::app()->charset);
	}

	/**
	 * @static
	 * @param $migrationName
	 * @return string
	 */
	public static function run($migrationName)
	{
		// migration names always start with timestamp m'yymmdd_hhmmss'
		$migrationShortName = substr(Blocks::app()->file->set($migrationName, false)->fileName, 1, 13);

		$runner = self::getRunner();
		$args = array('yiic', 'migrate', 'to', $migrationShortName, '--migrationTable='.Blocks::app()->config->databaseTablePrefix.'_migrations', '--interactive=0');

		ob_start();
		$runner->run($args);

		return htmlentities(ob_get_clean(), null, Blocks::app()->charset);
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
		$args = array('yiic', 'migrate', '--migrationTable='.Blocks::app()->config->databaseTablePrefix.'migrations', 'down', $number, '--interactive=0');

		ob_start();
		$runner->run($args);

		return htmlentities(ob_get_clean(), null, Blocks::app()->charset);
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

		return htmlentities(ob_get_clean(), null, Blocks::app()->charset);
	}

	/**
	 * @access private
	 * @static
	 * @return \CConsoleCommandRunner
	 */
	private static function getRunner()
	{
		$commandPath = Blocks::app()->path->commandsPath;
		$runner = new CConsoleCommandRunner();
		$runner->addCommands($commandPath);

		$commandPath = Blocks::app()->path->frameworkPath.'cli/commands';
		$runner->addCommands($commandPath);

		return $runner;
	}
}
