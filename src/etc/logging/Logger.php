<?php
namespace Craft;

/**
 * Class Logger
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.logging
 * @since     1.0
 */
class Logger extends \CLogger
{
	// Properties
	// =========================================================================

	/**
	 * This property will be passed as the parameter to {@link flush()} when it is called in {@link log()} due to the
	 * limit of {@link autoFlush} being reached. By default, this property is false, meaning the filtered messages are
	 * still kept in the memory by each log route after calling {@link flush()}. If this is true, the filtered messages
	 * will be written to the actual medium each time {@link flush()} is called within {@link log()}.
	 *
	 * @var boolean
	 */
	public $autoDump = true;

	/**
	 * How many messages should be logged before they are flushed to destinations. Defaults to 10,000, meaning for every
	 * 10,000 messages, the {@link flush} method will be automatically invoked once. If this is 0, it means messages
	 * will never be flushed automatically.
	 *
	 * @var integer
	 */
	public $autoFlush = 0;

	// Public Methods
	// =========================================================================

	/**
	 * Logs a message. Messages logged by this method may be retrieved back
	 * via {@link getLogs}.
	 *
	 * @param string $message  The message to be logged
	 * @param string $level    The level of the message (e.g. 'Trace', 'Warning', 'Error'). It is case-insensitive.
	 * @param bool   $force    Whether for force the message to be logged regardless of category or level.
	 * @param string $category The category of the message (e.g. 'system.web'). It is case-insensitive.
	 * @param string $plugin   The plugin handle that made the log call. If null, will be set to 'craft'. Use for
	 *                         determining which log file to write to.
	 *
	 * @return null
	 */
	public function log($message, $level = 'info', $force = false, $category = 'application', $plugin = null)
	{
		if (!$plugin)
		{
			$plugin = 'craft';
		}

		$this->_logs[] = array($message, $level, $category, microtime(true), $force, $plugin);
		$this->_logCount++;

		if ($this->autoFlush > 0 && $this->_logCount >= $this->autoFlush && !$this->_processing)
		{
			$this->_processing = true;
			$this->flush($this->autoDump);
			$this->_processing = false;
		}
	}

	/**
	 * Retrieves log messages.
	 *
	 * Messages may be filtered by log levels and/or categories.
	 *
	 * A level filter is specified by a list of levels separated by comma or space (e.g. 'trace, error'). A category
	 * filter is similar to level filter (e.g. 'system, system.web'). A difference is that in category filter you can
	 * use pattern like 'system.*' to indicate all categories starting with 'system'.
	 *
	 * If you do not specify level filter, it will bring back logs at all levels. The same applies to category filter.
	 *
	 * Level filter and category filter are combinational, i.e., only messages satisfying both filter conditions will
	 * be returned.
	 *
	 * @param string       $levels     level filter
	 * @param array|string $categories category filter
	 * @param array        $except
	 *
	 * @return array The list of messages. Each array element represents one message with the following structure:
	 *
	 *     array(
	 *        [0] => message (string)
	 *        [1] => level (string)
	 *        [2] => category (string)
	 *        [3] => timestamp (float, obtained by microtime(true)
	 *     );
	 */
	public function getLogs($levels = '', $categories = array(), $except = array())
	{
		$this->_levels = preg_split('/[\s,]+/', StringHelper::toLowerCase($levels), -1, PREG_SPLIT_NO_EMPTY);

		if (is_string($categories))
		{
			$this->_categories = preg_split('/[\s,]+/', StringHelper::toLowerCase($categories), -1, PREG_SPLIT_NO_EMPTY);
		}
		else
		{
			$this->_categories = array_filter(array_map(array('Craft\StringHelper', 'toLowerCase'), $categories));
		}

		if (is_string($except))
		{
			$this->_except = preg_split('/[\s,]+/', StringHelper::toLowerCase($except), -1, PREG_SPLIT_NO_EMPTY);
		}
		else
		{
			$this->_except = array_filter(array_map(array('Craft\StringHelper', 'toLowerCase'), $except));
		}

		$ret = $this->_logs;

		if (!empty($levels))
		{
			$ret = array_values(array_filter($ret, array($this, 'filterByLevel')));
		}

		if (!empty($this->_categories) || !empty($this->_except))
		{
			$ret = array_values(array_filter($ret, array($this, 'filterByCategory')));
		}

		return $ret;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * Filter function used by {@link getLogs}
	 *
	 * @param array $value The element to be filtered
	 *
	 * @return bool true if valid log, false if not.
	 */
	protected function filterByCategory($value)
	{
		return $this->filterAllCategories($value, 2);
	}

	/**
	 * Filter function used to filter included and excluded categories
	 *
	 * @param array $value The element to be filtered
	 * @param int   $index The index of the values array to be used for check
	 *
	 * @return bool true if valid timing entry, false if not.
	 */
	protected function filterAllCategories($value, $index)
	{
		$cat = StringHelper::toLowerCase($value[$index]);
		$ret = empty($this->_categories);

		foreach($this->_categories as $category)
		{
			if($cat === $category || (($c = rtrim($category, '.*')) !== $category && mb_strpos($cat, $c) === 0))
			{
				$ret = true;
			}
		}

		if ($ret)
		{
			foreach ($this->_except as $category)
			{
				if ($cat === $category || (($c = rtrim($category, '.*')) !== $category && mb_strpos($cat, $c) === 0))
				{
					$ret = false;
				}
			}
		}

		return $ret;
	}

	/**
	 * Filter function used by {@link getLogs}
	 *
	 * @param array $value The element to be filtered
	 *
	 * @return bool true if valid log, false if not.
	 */
	protected function filterByLevel($value)
	{
		if (isset($value[4]))
		{
			$force = $value[4];

			if ($force)
			{
				return true;
			}
		}

		return in_array(StringHelper::toLowerCase($value[1]), $this->_levels);
	}
}
