<?php
namespace Blocks;

/**
 *
 */
class ConsoleApplication extends \CConsoleApplication
{

	public function init()
	{
		$this->_importClasses();
		parent::init();
	}

	/**
	 * Prepares Yii's autoloader with a map pointing all of Blocks' class names to their file paths
	 */
	private function _importClasses()
	{
		$aliases = array(
			'business.*',
			'business.console.*',
			'business.console.commands.*',
			'business.datetime.*',
			'business.db.*',
			'business.email.*',
			'business.enums.*',
			'business.exceptions.*',
			'business.logging.*',
			'business.updates.*',
			'business.utils.*',
			'business.validators.*',
			'controllers.*',
			'migrations.*',
			'models.*',
			'models.forms.*',
			'services.*',
		);

		foreach ($aliases as $alias)
		{
			self::import($alias);
		}
	}

	/**
	 * @static
	 * @param      $alias
	 * @param bool $forceInclude
	 */
	public static function import($alias, $forceInclude = false)
	{
		$path = BLOCKS_APP_PATH.str_replace('.', '/', $alias);

		$directory = (substr($path, -2) == '/*');
		if ($directory)
		{
			$path = substr($path, 0, -1);

			if (($files = @glob($path."*.php")) !== false)
			{
				foreach ($files as $file)
				{
					self::_importFile(realpath($file));
				}
			}
		}
		else
		{
			$file = $path.'.php';
			self::_importFile($file);

			if ($forceInclude)
				require_once $file;
		}
	}

	/**
	 * @static
	 * @param $file
	 */
	private static function _importFile($file)
	{
		$class = __NAMESPACE__.'\\'.pathinfo($file, PATHINFO_FILENAME);
		\Yii::$classMap[$class] = $file;
	}

	/**
	 * @return ConsoleCommandRunner
	 */
	protected function createCommandRunner()
	{
		return new ConsoleCommandRunner();
	}
}
