<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mutex;

use Craft;
use yii\base\Application;
use yii\db\Connection;

/**
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.30
 * @mixin Mutex
 */
trait MutexTrait
{
    /**
     * @var string a string prefixed to every lock name. This can be used to avoid lock conflicts if
     * multiple applications are sharing the same database connection.
     */
    public string $namePrefix = '';

    /**
     * @var Connection
     */
    private Connection $_db;

    /**
     * @var array List of mutex locks that are queued to be released once the current DB transaction is complete.
     */
    private array $_releaseQueue = [];

    /**
     * Initializes the component.
     */
    public function init(): void
    {
        parent::init();

        $this->_db = Craft::$app->getDb();
        $this->_db->on(Connection::EVENT_COMMIT_TRANSACTION, [$this, 'releaseQueuedLocks']);
        $this->_db->on(Connection::EVENT_ROLLBACK_TRANSACTION, [$this, 'releaseQueuedLocks']);
        Craft::$app->on(Application::EVENT_AFTER_REQUEST, [$this, 'releaseQueuedLocks']);
    }

    /**
     * @param string $name
     * @param int $timeout
     * @return bool
     */
    public function acquire($name, $timeout = 0): bool
    {
        $name = $this->_name($name);

        // Is it already acquired in the DB, waiting for the transaction to be completed?
        if (isset($this->_releaseQueue[$name])) {
            unset($this->_releaseQueue[$name]);
            return true;
        }

        return parent::acquire($name, $timeout);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function release($name): bool
    {
        $name = $this->_name($name);

        // Is there an active transaction?
        if ($this->_db->getTransaction() !== null) {
            $this->_releaseQueue[$name] = true;
            return true;
        }

        return parent::release($name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function isAcquired($name): bool
    {
        $name = $this->_name($name);

        // Is it already acquired in the DB, waiting for the transaction to be completed?
        if (isset($this->_releaseQueue[$name])) {
            // Pretend it’s not
            return false;
        }

        return parent::isAcquired($name);
    }

    /**
     * @param string $name
     * @return string
     */
    private function _name(string $name): string
    {
        return $this->namePrefix . $name;
    }

    /**
     * Releases any locks that are waiting on the DB transaction to complete.
     */
    public function releaseQueuedLocks(): void
    {
        foreach (array_keys($this->_releaseQueue) as $name) {
            parent::release($name);
        }
        $this->_releaseQueue = [];
    }
}
