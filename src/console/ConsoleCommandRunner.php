<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\etc\console;

use Craft;
use craft\app\helpers\IOHelper;
use craft\app\helpers\StringHelper;

/**
 * Class ConsoleCommandRunner
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ConsoleCommandRunner extends \CConsoleCommandRunner
{
	// Public Methods
	// =========================================================================

	/**
	 * @param string $name command name (case-insensitive)
	 *
	 * @return \CConsoleCommand The command object. Null if the name is invalid.
	 */
	public function createCommand($name)
	{
		$name = StringHelper::toLowerCase($name);

		$command = null;

		if (isset($this->commands[$name]))
		{
			$command = $this->commands[$name];
		}
		else
		{
			$commands = array_change_key_case($this->commands);

			if (isset($commands[$name]))
			{
				$command = $commands[$name];
			}
		}

		if ($command !== null)
		{
			if (is_string($command))  // class file path or alias
			{
				if (StringHelper::containsAny($command, array('/', '\\')))
				{
					$className = IOHelper::getFileName($command, false);

					// If it's a default framework command, don't namespace it.
					if (!StringHelper::contains($command, 'framework'))
					{
						$className = __NAMESPACE__.'\\'.$className;
					}

					if (!class_exists($className, false))
					{
						require_once($command);
					}
				}
				else // an alias
				{
					$className = Craft::import($command);
				}

				return new $className($name, $this);
			}
			else // an array configuration
			{
				return Craft::createComponent($command, $name, $this);
			}
		}
		else if ($name === 'help')
		{
			return new \CHelpCommand('help', $this);
		}
		else
		{
			return null;
		}
	}

	/**
	 * Adds commands from the specified command path. If a command already exists, the new one will overwrite it.
	 *
	 * @param string $path The alias of the folder containing the command class files.
	 *
	 * @return null
	 */
	public function addCommands($path)
	{
		if (($commands = $this->findCommands($path)) !== [])
		{
			foreach($commands as $name => $file)
			{
				$this->commands[$name] = $file;
			}
		}
	}
}
