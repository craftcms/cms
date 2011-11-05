<?php

class Migration
{
	public static function runToTop()
	{
		$runner = self::getRunner();
		$args = array('yiic', 'migrate', '--migrationTable='.Blocks::app()->config->getDatabaseTablePrefix().'_migrations', '--interactive=0');

		ob_start();
		$runner->run($args);

		return htmlentities(ob_get_clean(), null, Blocks::app()->charset);
	}

	public static function run($migrationName)
	{
		// migration names always start with timestamp m'yymmdd_hhmmss'
		$migrationShortName = substr(Blocks::app()->file->set($migrationName, false)->getFileName(), 1, 13);

		$runner = self::getRunner();
		$args = array('yiic', 'migrate', 'to', $migrationShortName, '--migrationTable='.Blocks::app()->config->getDatabaseTablePrefix().'_migrations', '--interactive=0');

		ob_start();
		$runner->run($args);

		return htmlentities(ob_get_clean(), null, Blocks::app()->charset);
	}

	public static function revert($number)
	{
		if (!isset($number))
			$number = 1;

		$runner = self::getRunner();
		$args = array('yiic', 'migrate', '--migrationTable='.Blocks::app()->config->getDatabaseTablePrefix().'migrations', 'down', $number, '--interactive=0');

		ob_start();
		$runner->run($args);

		return htmlentities(ob_get_clean(), null, Blocks::app()->charset);
	}

	public static function create($migrationName)
	{
		$migrationName = str_replace(' ', '_', $migrationName);
		$runner = self::getRunner();
		$args = array('yiic', 'migrate', 'create', $migrationName, '--interactive=0');

		ob_start();
		$runner->run($args);

		return htmlentities(ob_get_clean(), null, Blocks::app()->charset);
	}

	private static function getRunner()
	{
		$commandPath = Blocks::app()->getBasePath().DIRECTORY_SEPARATOR.'commands';
		$runner = new CConsoleCommandRunner();
		$runner->addCommands($commandPath);

		$commandPath = Blocks::getFrameworkPath().DIRECTORY_SEPARATOR.'cli'.DIRECTORY_SEPARATOR.'commands';
		$runner->addCommands($commandPath);

		return $runner;
	}
}
