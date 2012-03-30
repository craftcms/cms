<?php
namespace Blocks;

/**
 *
 */
class ConsoleCommandRunner extends \CConsoleCommandRunner
{
	/**
	 * @param string $name command name (case-insensitive)
	 * @return \CConsoleCommand the command object. Null if the name is invalid.
	 */
	public function createCommand($name)
	{
		$name = strtolower($name);
		if (isset($this->commands[$name]))
		{
			if (is_string($this->commands[$name]))  // class file path or alias
			{
				if (strpos($this->commands[$name], '/') !== false || strpos($this->commands[$name], '\\') !== false)
				{
					$className = __NAMESPACE__.'\\'.substr(basename($this->commands[$name]), 0, -4);
					if (!class_exists($className, false))
						require_once($this->commands[$name]);
				}
				else // an alias
					$className = Blocks::import($this->commands[$name]);

				return new $className($name, $this);
			}
			else // an array configuration
				return Blocks::createComponent($this->commands[$name], $name, $this);
		}
		else if ($name === 'help')
			return new \CHelpCommand('help', $this);
		else
			return null;
	}
}
