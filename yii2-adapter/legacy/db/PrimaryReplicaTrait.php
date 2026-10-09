<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\db;

use PDO;
use Throwable;
use yii\db\Connection as YiiConnection;

/**
 * @property bool $enableReplicas whether to enable read/write splitting by using [[replicas]] to read data.
 * Note that if [[replicas]] is empty, read/write splitting will NOT be enabled no matter what value this property takes.
 * @property array $replicas list of replica connection configurations. Each configuration is used to create a replica DB connection.
 * When [[enableReplicas]] is true, one of these configurations will be chosen and used to create a DB connection
 * for performing read queries only.
 * @property array $replicaConfig the configuration that should be merged with every replica configuration listed in [[replicas]].
 * For example,
 *
 * ```php
 * [
 *     'username' => 'replica',
 *     'password' => 'replica',
 *     'attributes' => [
 *         // use a smaller connection timeout
 *         PDO::ATTR_TIMEOUT => 10,
 *     ],
 * ]
 * ```
 * @property array $primaries list of primary connection configurations. Each configuration is used to create a primary DB connection.
 * When [[open()]] is called, one of these configurations will be chosen and used to create a DB connection
 * which will be used by this object.
 * Note that when this property is not empty, the connection setting (e.g. `dsn`, `username`) of this object will
 * be ignored.
 * @property array $primaryConfig the configuration that should be merged with every primary configuration listed in [[primaries]].
 * For example,
 *
 * ```php
 * [
 *     'username' => 'primary',
 *     'password' => 'primary',
 *     'attributes' => [
 *         // use a smaller connection timeout
 *         PDO::ATTR_TIMEOUT => 10,
 *     ],
 * ]
 * ```
 * @property bool $shufflePrimaries whether to shuffle [[primaries]] before getting one.
 * @property-read YiiConnection|null $primary The currently active primary connection. `null` is returned if no primary
 * connection is available. This property is read-only.
 * @property-read PDO $primaryPdo The PDO instance for the currently active primary connection. This property is
 * read-only.
 * @property-read YiiConnection $replica The currently active replica connection. This property is read-only.
 * @property-read PDO $replicaPdo The PDO instance for the currently active replica connection. This property
 * is read-only.
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.25
 */
trait PrimaryReplicaTrait
{
    /**
     * Returns the value of [[enableReplicas]].
     *
     * @return bool
     * @internal
     */
    public function getEnableReplicas(): bool
    {
        return $this->enableSlaves;
    }

    /**
     * Sets the value of [[enableReplicas]].
     *
     * @param bool $value
     * @internal
     */
    public function setEnableReplicas(bool $value): void
    {
        $this->enableSlaves = $value;
    }

    /**
     * Returns the value of [[replicas]].
     *
     * @return array
     * @internal
     */
    public function getReplicas(): array
    {
        return $this->slaves;
    }

    /**
     * Sets the value of [[replicas]].
     *
     * @param array $value
     * @internal
     */
    public function setReplicas(array $value): void
    {
        $this->slaves = $value;
    }

    /**
     * Returns the value of [[replicaConfig]].
     *
     * @return array
     * @internal
     */
    public function getReplicaConfig(): array
    {
        return $this->slaveConfig;
    }

    /**
     * Sets the value of [[replicaConfig]].
     *
     * @param array $value
     * @internal
     */
    public function setReplicaConfig(array $value): void
    {
        $this->slaveConfig = $value;
    }

    /**
     * Returns the value of [[primaries]].
     *
     * @return array
     * @internal
     */
    public function getPrimaries(): array
    {
        return $this->masters;
    }

    /**
     * Sets the value of [[primaries]].
     *
     * @param array $value
     * @internal
     */
    public function setPrimaries(array $value): void
    {
        $this->masters = $value;
    }

    /**
     * Returns the value of [[primaryConfig]].
     *
     * @return array
     * @internal
     */
    public function getPrimaryConfig(): array
    {
        return $this->masterConfig;
    }

    /**
     * Sets the value of [[primaryConfig]].
     *
     * @param array $value
     * @internal
     */
    public function setPrimaryConfig(array $value): void
    {
        $this->masterConfig = $value;
    }

    /**
     * Returns the value of [[shufflePrimaries]].
     *
     * @return bool
     * @internal
     */
    public function getShufflePrimaries(): bool
    {
        return $this->shuffleMasters;
    }

    /**
     * Sets the value of [[shufflePrimaries]].
     *
     * @param bool $value
     * @internal
     */
    public function setShufflePrimaries(bool $value): void
    {
        $this->shuffleMasters = $value;
    }

    /**
     * Returns the PDO instance for the currently active replica connection.
     * When [[enableReplicas]] is true, one of the replicas will be used for read queries, and its PDO instance
     * will be returned by this method.
     *
     * @param bool $fallbackToPrimary whether to return the primary PDO if no replica connections are available.
     * @return PDO|null the PDO instance for the currently active replica connection. `null` is returned if no
     * replica connections are available and `$fallbackToPrimary` is false.
     */
    public function getReplicaPdo(bool $fallbackToPrimary = true): ?PDO
    {
        return $this->getSlavePdo($fallbackToPrimary);
    }

    /**
     * Returns the PDO instance for the currently active primary connection.
     * This method will open the primary DB connection and then return [[pdo]].
     *
     * @return PDO the PDO instance for the currently active primary connection.
     */
    public function getPrimaryPdo(): PDO
    {
        return $this->getMasterPdo();
    }

    /**
     * Returns the currently active replica connection.
     * If this method is called for the first time, it will try to open a replica connection when [[enableReplicas]]
     * is true.
     *
     * @param bool $fallbackToPrimary whether to return the primary connection if no replica connections are
     * available.
     * @return YiiConnection|null the currently active replica connection. `null` is returned if no replica connections
     * are available and `$fallbackToPrimary` is false.
     */
    public function getReplica(bool $fallbackToPrimary = true): ?YiiConnection
    {
        return $this->getSlave($fallbackToPrimary);
    }

    /**
     * Returns the currently active primary connection.
     * If this method is called for the first time, it will try to open a primary connection.
     *
     * @return YiiConnection|null the currently active primary connection. `null` is returned if no primary connection
     * is available.
     */
    public function getPrimary(): ?YiiConnection
    {
        return $this->getMaster();
    }

    /**
     * Executes the provided callback by using the primary connection.
     *
     * This method is provided so that you can temporarily force using the primary connection to perform
     * DB operations even if they are read queries. For example,
     *
     * ```php
     * $result = $db->usePrimary(function ($db) {
     *     return $db->createCommand('SELECT * FROM user LIMIT 1')->queryOne();
     * });
     * ```
     *
     * @param callable $callback a PHP callable to be executed by this method. Its signature is
     * `function (Connection $db)`. Its return value will be returned by this method.
     * @return mixed the return value of the callback
     * @throws Throwable if there is any exception thrown from the callback
     */
    public function usePrimary(callable $callback): mixed
    {
        return $this->useMaster($callback);
    }
}
