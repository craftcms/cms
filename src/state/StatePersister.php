<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\state;

use craft\app\Craft;
use craft\app\helpers\IOHelper;

/**
 * StatePersister implements a file-based persistent data storage.
 *
 * It can be used to keep data available through multiple requests and sessions.
 *
 * By default, StatePersister stores data in a file named 'state.bin' that is located [[\craft\app\services\Path::getStatePath()]].
 *
 * To retrieve the data from StatePersister, call [[load()]]. To save the data, call [[save()]].
 *
 * A comparison among state persister, session and cache is as follows:
 *
 * * Session: data persisting within a single user session.
 * * State Persister: data persisting through all requests/sessions (e.g. hit counter).
 * * Cache: volatile and fast storage. It may be used as storage medium for session or state persister.
 *
 * Since server resource is often limited, be cautious if you plan to use StatePersister to store large amount of data.
 * You should also consider using database-based persister to improve the throughput.
 *
 * StatePersister is a core application component used to store global application state. It may be accessed via
 * [[\craft\app\web\Application::getStatePersister()]].
 *
 *
 * Craft overrides the default [[\CStatePersister]] so it can set a custom path at runtime for our state file.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class StatePersister extends \CStatePersister
{
	// Public Methods
	// =========================================================================

	/**
	 * Initializes the component.
	 *
	 * @return null
	 */
	public function init()
	{
		$this->stateFile = Craft::$app->path->getStatePath().'state.bin';
		parent::init();
	}

	/**
	 * Saves application state in persistent storage.
	 *
	 * @param mixed $state The state data (must be serializable).
	 */
	public function save($state)
	{
		IOHelper::writeToFile($this->stateFile, serialize($state));
	}
}
