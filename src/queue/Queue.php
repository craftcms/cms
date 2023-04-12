<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue;

use Craft;
use craft\db\Connection;
use craft\db\Table;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\Queue as QueueHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\i18n\Translation;
use craft\queue\jobs\Proxy;
use DateTime;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\db\Expression;
use yii\db\Query;
use yii\di\Instance;
use yii\mutex\Mutex;
use yii\queue\ExecEvent;
use yii\queue\Queue as BaseQueue;
use yii\web\Response;

/**
 * Craft Queue
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 * @since 3.0.0
 */
class Queue extends \yii\queue\cli\Queue implements QueueInterface
{
    /**
     * @see isFailed()
     */
    public const STATUS_FAILED = 4;

    /**
     * @var Connection|array|string The database connection to use
     * @since 3.4.0
     */
    public string|array|Connection $db = 'db';

    /**
     * @var Mutex|array|string The mutex component to use
     * @since 3.4.0
     */
    public Mutex|string|array $mutex = 'mutex';

    /**
     * @var int The time (in seconds) to wait for mutex locks to be released when attempting to reserve new jobs.
     */
    public int $mutexTimeout = 5;

    /**
     * @var string The table name the queue is stored in.
     * @since 3.4.0
     */
    public string $tableName = Table::QUEUE;

    /**
     * @var string|null The `channel` column value to the queue should use. If null, the queue’s application component ID will be used.
     * @since 3.4.0
     */
    public ?string $channel = null;

    /**
     * @inheritdoc
     */
    public $commandClass = Command::class;

    /**
     * @var BaseQueue|array|string|null An external queue that proxy jobs should be sent to.
     *
     * If this is set, [[push()]] will send [[Proxy]] jobs to it that reference the internal job IDs.
     * When executed, those jobs will cause the referenced internal jobs to be executed, unless they’ve
     * already been run directly.
     *
     * @since 4.0.0
     */
    public BaseQueue|array|string|null $proxyQueue = null;

    /**
     * @var string|null The description of the job being pushed into the queue
     */
    private ?string $_jobDescription = null;

    /**
     * @var string|null The currently-executing job ID
     */
    private ?string $_executingJobId = null;

    /**
     * @var int|null The timestamp the last job was reserved
     */
    private ?int $_reserveTime = null;

    /**
     * @var bool Whether we're already listening for the web response
     */
    private bool $_listeningForResponse = false;

    /**
     * @var bool Whether a mutex lock has been acquired
     * @see _lock()
     */
    private bool $_locked = false;

    /**
     * @var string|null The application component ID
     * @see componentId()
     */
    private ?string $_componentId = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->db = Instance::ensure($this->db, Connection::class);
        $this->mutex = Instance::ensure($this->mutex, Mutex::class);

        if (isset($this->proxyQueue)) {
            $this->proxyQueue = Instance::ensure($this->proxyQueue, BaseQueue::class);
        }

        $this->on(self::EVENT_BEFORE_EXEC, function(ExecEvent $e) {
            $this->_executingJobId = $e->id;
        });

        $this->on(self::EVENT_AFTER_EXEC, function(ExecEvent $e) {
            $this->_executingJobId = null;
        });
    }

    /**
     * @inheritdoc
     * @param bool $repeat Whether to continue listening when the queue is empty.
     * @param int $timeout The number of seconds to wait between cycles
     * @return int|null the exit code
     */
    public function run(bool $repeat = false, int $timeout = 0): ?int
    {
        return $this->runWorker(function(callable $canContinue) use ($repeat, $timeout) {
            while ($canContinue()) {
                if (!$this->executeJob()) {
                    if (!$repeat) {
                        break;
                    } elseif ($timeout) {
                        sleep($timeout);
                    }
                }
            }
        });
    }

    /**
     * Executes a single job.
     *
     * @param string|null $id The job ID, if a specific job should be run
     * @return bool Whether a job was found
     */
    public function executeJob(?string $id = null): bool
    {
        $payload = $this->reserve($id);

        if (!$payload) {
            return false;
        }

        if ($this->handleMessage($payload['id'], $payload['job'], $payload['ttr'], $payload['attempt'])) {
            $this->release($payload['id']);
        }

        return true;
    }

    /**
     * Listens to the queue and runs new jobs.
     *
     * @param int $timeout The number of seconds to wait between cycles
     * @return int|null the exit code
     * @deprecated in 3.6.11. Use [[run()]] instead.
     */
    public function listen(int $timeout = 0): ?int
    {
        return $this->run(true, $timeout);
    }

    /**
     * @param string $id The job ID.
     * @return bool
     */
    public function isFailed(string $id): bool
    {
        return $this->status($id) === self::STATUS_FAILED;
    }

    /**
     * @inheritdoc
     */
    public function status($id): int
    {
        $payload = $this->db->usePrimary(function() use ($id) {
            return $this->_createJobQuery()
                ->select(['fail', 'timeUpdated'])
                // No need to use andWhere() here since we're fetching by ID
                ->where(['id' => $id])
                ->one($this->db);
        });

        return $this->_status($payload);
    }

    /**
     * @inheritdoc
     */
    public function push($job): ?string
    {
        // Capture the description so pushMessage() can access it
        if ($job instanceof JobInterface) {
            $this->_jobDescription = $job->getDescription();
        } else {
            $this->_jobDescription = null;
        }

        if (($id = parent::push($job)) === null) {
            return null;
        }

        // Have the response kick off a new queue runner if this is a site request
        if (Craft::$app->getConfig()->getGeneral()->runQueueAutomatically && !$this->_listeningForResponse) {
            $request = Craft::$app->getRequest();
            if ($request->getIsSiteRequest() && !$request->getIsAjax()) {
                Craft::$app->getResponse()->on(Response::EVENT_AFTER_PREPARE, [$this, 'handleResponse']);
                $this->_listeningForResponse = true;
            }
        }

        return $id;
    }

    /**
     * @inheritdoc
     */
    public function retry(string $id): void
    {
        $this->_retry([
            'id' => $id,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function retryAll(): void
    {
        $this->_retry([
            'channel' => $this->channel(),
            'fail' => true,
        ]);
    }

    private function _retry(array $condition): void
    {
        $this->_lock(function() use ($condition) {
            // Move expired messages into waiting list
            $this->_moveExpired();

            if ($this->proxyQueue) {
                $jobs = (new Query())
                    ->select(['id', 'priority', 'delay', 'ttr'])
                    ->from($this->tableName)
                    ->where($condition)
                    ->all();

                foreach ($jobs as $job) {
                    Db::update($this->tableName, [
                        'dateReserved' => null,
                        'timeUpdated' => null,
                        'progress' => 0,
                        'progressLabel' => null,
                        'attempt' => 0,
                        'fail' => false,
                        'dateFailed' => null,
                        'error' => null,
                    ], [
                        'id' => $job['id'],
                    ], [], false, $this->db);

                    $this->pushProxyJob($job['id'], $job['priority'], $job['delay'], $job['ttr']);
                }
            } else {
                Db::update($this->tableName, [
                    'dateReserved' => null,
                    'timeUpdated' => null,
                    'progress' => 0,
                    'progressLabel' => null,
                    'attempt' => 0,
                    'fail' => false,
                    'dateFailed' => null,
                    'error' => null,
                ], $condition, updateTimestamp: false, db: $this->db);
            }
        });
    }

    /**
     * @inheritdoc
     */
    public function release(string $id): void
    {
        $this->_lock(function() use ($id) {
            Db::delete($this->tableName, [
                'id' => $id,
            ], [], $this->db);
        });
    }

    /**
     * @inheritdoc
     */
    public function releaseAll(): void
    {
        $this->_lock(function() {
            Db::delete($this->tableName, [
                'channel' => $this->channel(),
            ], [], $this->db);
        });
    }

    /**
     * @inheritdoc
     */
    public function setProgress(int $progress, ?string $label = null): void
    {
        $this->_lock(function() use ($progress, $label) {
            $data = [
                'progress' => $progress,
                'timeUpdated' => time(),
            ];

            if ($label !== null) {
                $data['progressLabel'] = $label;
            }

            Db::update($this->tableName, $data, [
                'id' => $this->_executingJobId,
            ], [], false, $this->db);
        });
    }

    /**
     * @inheritdoc
     */
    public function getHasWaitingJobs(): bool
    {
        // Move expired messages into waiting list
        $this->_moveExpired(0, false);

        return $this->db->usePrimary(function() {
            return $this->_createWaitingJobQuery()->exists($this->db);
        });
    }

    /**
     * @inheritdoc
     */
    public function getHasReservedJobs(): bool
    {
        // Move expired messages into waiting list
        $this->_moveExpired(0, false);

        return $this->db->usePrimary(function() {
            return $this->_createReservedJobQuery()->exists($this->db);
        });
    }

    /**
     * Returns the total number of waiting jobs
     *
     * @return int
     */
    public function getTotalWaiting(): int
    {
        // Move expired messages into waiting list
        $this->_moveExpired(0, false);

        return $this->db->usePrimary(function() {
            return $this->_createWaitingJobQuery()->count('*', $this->db);
        });
    }

    /**
     * Returns the total number of delayed jobs
     *
     * @return int
     */
    public function getTotalDelayed(): int
    {
        // Move expired messages into waiting list
        $this->_moveExpired(0, false);

        return $this->db->usePrimary(function() {
            return $this->_createDelayedJobQuery()->count('*', $this->db);
        });
    }

    /**
     * Returns the total number of reserved jobs
     *
     * @return int
     */
    public function getTotalReserved(): int
    {
        // Move expired messages into waiting list
        $this->_moveExpired(0, false);

        return $this->db->usePrimary(function() {
            return $this->_createReservedJobQuery()->count('*', $this->db);
        });
    }

    /**
     * Returns the total number of failed jobs
     *
     * @return int
     */
    public function getTotalFailed(): int
    {
        // Move expired messages into waiting list
        $this->_moveExpired(0, false);

        return $this->db->usePrimary(function() {
            return $this->_createFailedJobQuery()->count('*', $this->db);
        });
    }

    /**
     * @inheritdoc
     */
    public function getJobDetails(string $id): array
    {
        $result = $this->db->usePrimary(function() use ($id) {
            return (new Query())
                ->from($this->tableName)
                ->where(['id' => $id])
                ->one($this->db);
        });

        if ($result === false) {
            throw new InvalidArgumentException("Invalid job ID: $id");
        }

        $formatter = Craft::$app->getFormatter();
        $job = $this->serializer->unserialize($this->_jobData($result['job']));

        return ArrayHelper::filterEmptyStringsFromArray([
            'delay' => max(0, $result['timePushed'] + $result['delay'] - time()),
            'status' => $this->_status($result),
            'error' => $result['error'] ?? '',
            'progress' => $result['progress'],
            'progressLabel' => Translation::translate((string)$result['progressLabel']) ?: null,
            'description' => Translation::translate((string)$result['description']) ?: null,
            'job' => $job,
            'ttr' => (int)$result['ttr'],
            'Priority' => $result['priority'],
            'Pushed at' => $result['timePushed'] ? $formatter->asDatetime($result['timePushed']) : '',
            'Updated at' => $result['timeUpdated'] ? $formatter->asDatetime($result['timeUpdated']) : '',
            'Failed at' => $result['dateFailed'] ? $formatter->asDatetime($result['dateFailed']) : '',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getTotalJobs(): int
    {
        return $this->db->usePrimary(function() {
            return $this->_createJobQuery()
                ->count('*', $this->db);
        });
    }

    /**
     * @inheritdoc
     */
    public function getJobInfo(?int $limit = null): array
    {
        // Move expired messages into waiting list
        $this->_moveExpired(0, false);

        $query = $this->_createJobQuery();

        // Set up the reserved jobs condition
        $reservedCondition = $this->db->getQueryBuilder()->buildCondition([
            'and',
            ['fail' => false],
            ['not', ['timeUpdated' => null]],
        ], $query->params);

        $query
            ->select(['id', 'description', 'timePushed', 'delay', 'progress', 'progressLabel', 'timeUpdated', 'fail', 'error'])
            ->orderBy(new Expression("CASE WHEN $reservedCondition THEN 1 ELSE 0 END DESC"))
            ->addOrderBy(['priority' => SORT_ASC, 'id' => SORT_ASC])
            ->limit($limit);

        $results = $this->db->usePrimary(function() use ($query) {
            return $query->all($this->db);
        });

        $info = [];

        foreach ($results as $result) {
            if (!App::devMode() && !Craft::$app->getUser()->getIsAdmin()) {
                $result['error'] = Craft::t('app', 'A server error occurred.');
            }

            $info[] = [
                'id' => $result['id'],
                'delay' => max(0, $result['timePushed'] + $result['delay'] - time()),
                'status' => $this->_status($result),
                'progress' => (int)$result['progress'],
                'progressLabel' => Translation::translate((string)$result['progressLabel']) ?: null,
                'description' => Translation::translate((string)$result['description']) ?: null,
                'error' => $result['error'],
            ];
        }

        return $info;
    }

    /**
     * @inheritdoc
     */
    public function handleError(ExecEvent $event): bool
    {
        $this->_executingJobId = null;

        // Have we given up?
        if (parent::handleError($event)) {
            // Mark the job as failed
            $this->_lock(function() use ($event) {
                if ($event->error) {
                    Craft::$app->getErrorHandler()->logException($event->error);
                }
                Db::update($this->tableName, [
                    'fail' => true,
                    'dateFailed' => Db::prepareDateForDb(new DateTime()),
                    'error' => $event->error ? $this->_truncateErrorMessage($event->error->getMessage()) : null,
                ], [
                    'id' => $event->id,
                ], [], false, $this->db);
            });
        }

        // Don't tell run() to release the job
        return false;
    }

    /**
     * Figure out how to initiate a new worker.
     */
    public function handleResponse(): void
    {
        // Prevent this from getting called twice
        $response = Craft::$app->getResponse();
        $response->off(Response::EVENT_AFTER_PREPARE, [$this, 'handleResponse']);

        // Ignore if any jobs are currently reserved
        if ($this->getHasReservedJobs()) {
            return;
        }

        // Ignore if this isn't an HTML/XHTML response
        if (!in_array($response->getContentType(), ['text/html', 'application/xhtml+xml'], true)) {
            return;
        }

        // Include JS that tells the browser to fire an Ajax request to kick off a new queue runner
        // (Ajax request code adapted from http://www.quirksmode.org/js/xmlhttp.html - thanks ppk!)
        $url = Json::encode(UrlHelper::actionUrl('queue/run', null, null, false));
        $js = <<<EOD
<script type="text/javascript">
/*<![CDATA[*/
(function(){
    var XMLHttpFactories = [
        function () {return new XMLHttpRequest()},
        function () {return new ActiveXObject("Msxml2.XMLHTTP")},
        function () {return new ActiveXObject("Msxml3.XMLHTTP")},
        function () {return new ActiveXObject("Microsoft.XMLHTTP")}
    ];
    var req = false;
    for (var i = 0; i < XMLHttpFactories.length; i++) {
        try {
            req = XMLHttpFactories[i]();
        }
        catch (e) {
            continue;
        }
        break;
    }
    if (!req) return;
    req.open('GET', $url, true);
    if (req.readyState == 4) return;
    req.send();
})();
/*]]>*/
</script>
EOD;

        if ($response->content === null) {
            $response->content = $js;
        } else {
            $response->content .= $js;
        }
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    protected function pushMessage($message, $ttr, $delay, $priority): string
    {
        Db::insert($this->tableName, [
            'channel' => $this->channel(),
            'job' => $message,
            'description' => $this->_jobDescription,
            'timePushed' => time(),
            'ttr' => $ttr,
            'delay' => $delay,
            'priority' => $priority ?: 1024,
        ], $this->db);

        $id = $this->db->getLastInsertID($this->tableName);

        // If there's a proxy queue, send a job to that as well
        if ($this->proxyQueue) {
            $this->pushProxyJob($id, $priority, $delay, $ttr);
        }

        return $id;
    }

    /**
     * Pushes a new job to the proxy queue.
     *
     * @param string $id
     * @param int|null $priority
     * @param int|null $delay
     * @param int|null $ttr
     */
    private function pushProxyJob(string $id, ?int $priority, ?int $delay, ?int $ttr)
    {
        $job = new Proxy([
            'queue' => $this->componentId(),
            'jobId' => $id,
        ]);
        QueueHelper::push($job, $priority, $delay, $ttr, $this->proxyQueue);
    }

    /**
     * @return string The component ID
     * @throws InvalidConfigException
     */
    private function componentId(): string
    {
        if (!isset($this->_componentId)) {
            foreach (Craft::$app->getComponents(false) as $id => $component) {
                if ($component === $this) {
                    $this->_componentId = $id;
                    break;
                }
            }
            if (!isset($this->_componentId)) {
                throw new InvalidConfigException('Queue must be an application component.');
            }
        }

        return $this->_componentId;
    }

    /**
     * @param string|null $id The job ID
     * @return array|null The payload, or null if there aren't any jobs to reserve
     * @throws Exception in case it hasn't waited the lock
     */
    protected function reserve(?string $id = null): ?array
    {
        $payload = null;

        $this->_lock(function() use (&$payload, $id) {
            // Move expired messages into waiting list
            $this->_moveExpired();

            // Reserve one message
            /** @var array|null $payload */
            $payload = $this->db->usePrimary(function() use ($id) {
                $query = $this->_createJobQuery()
                    ->andWhere(['fail' => false, 'timeUpdated' => null])
                    ->andWhere('[[timePushed]] + [[delay]] <= :time', ['time' => time()])
                    ->orderBy(['priority' => SORT_ASC, 'id' => SORT_ASC])
                    ->limit(1);

                if ($id) {
                    $query->andWhere(['id' => $id]);
                }

                return $query->one($this->db) ?: null;
            });

            if (is_array($payload)) {
                $payload['dateReserved'] = new DateTime();
                $payload['timeUpdated'] = $payload['dateReserved']->getTimestamp();
                $payload['attempt'] = (int)$payload['attempt'] + 1;
                Db::update($this->tableName, [
                    'dateReserved' => Db::prepareDateForDb($payload['dateReserved']),
                    'timeUpdated' => $payload['timeUpdated'],
                    'attempt' => $payload['attempt'],
                ], [
                    'id' => $payload['id'],
                ], [], false, $this->db);
            }
        });

        // pgsql
        if (is_array($payload)) {
            $payload['job'] = $this->_jobData($payload['job']);
        }

        return $payload;
    }

    /**
     * Checks if $job is a resource and if so, convert it to a serialized format.
     *
     * @param string|resource $job
     * @return string
     */
    private function _jobData(mixed $job): string
    {
        if (is_resource($job)) {
            $job = stream_get_contents($job);

            if (is_string($job) && str_starts_with($job, 'x')) {
                $hex = substr($job, 1);
                if (StringHelper::isHexadecimal($hex)) {
                    $job = hex2bin($hex);
                }
            }
        }

        return $job;
    }

    /**
     * Moves expired messages into waiting list.
     *
     * @param int|null $mutexTimeout
     * @param bool $throwMutexException
     */
    private function _moveExpired(?int $mutexTimeout = null, bool $throwMutexException = true): void
    {
        if ($this->_reserveTime !== time()) {
            $this->_lock(function() {
                $this->_reserveTime = time();

                $expiredIds = $this->db->usePrimary(function() {
                    return (new Query())
                        ->select(['id'])
                        ->from([$this->tableName])
                        ->where([
                            'and',
                            [
                                'channel' => $this->channel(),
                                'fail' => false,
                            ],
                            '[[timeUpdated]] < :time - [[ttr]]',
                        ], [
                            ':time' => $this->_reserveTime,
                        ])
                        ->column($this->db);
                });

                if (!empty($expiredIds)) {
                    Db::update($this->tableName, [
                        'dateReserved' => null,
                        'timeUpdated' => null,
                        'progress' => 0,
                        'progressLabel' => null,
                    ], ['id' => $expiredIds], [], false, $this->db);
                }
            }, $mutexTimeout, $throwMutexException);
        }
    }

    /**
     * Returns a new query for jobs.
     *
     * @return Query
     */
    private function _createJobQuery(): Query
    {
        return (new Query())
            ->from($this->tableName)
            ->where(['channel' => $this->channel()]);
    }

    /**
     * Returns the `channel` value to use.
     *
     * @return string
     * @throws InvalidConfigException
     */
    private function channel(): string
    {
        return $this->channel ?? $this->componentId();
    }

    /**
     * Returns a new query for waiting jobs.
     *
     * @return Query
     */
    private function _createWaitingJobQuery(): Query
    {
        return $this->_createJobQuery()
            ->andWhere(['fail' => false, 'timeUpdated' => null])
            ->andWhere('[[timePushed]] + [[delay]] <= :time', ['time' => time()]);
    }

    /**
     * Returns a new query for delayed jobs.
     *
     * @return Query
     */
    private function _createDelayedJobQuery(): Query
    {
        return $this->_createJobQuery()
            ->andWhere(['fail' => false, 'timeUpdated' => null])
            ->andWhere('[[timePushed]] + [[delay]] > :time', ['time' => time()]);
    }

    /**
     * Returns a new query for reserved jobs.
     *
     * @return Query
     */
    private function _createReservedJobQuery(): Query
    {
        return $this->_createJobQuery()
            ->andWhere(['and', ['fail' => false], ['not', ['timeUpdated' => null]]]);
    }

    /**
     * Returns a new query for failed jobs.
     *
     * @return Query
     */
    private function _createFailedJobQuery(): Query
    {
        return $this->_createJobQuery()
            ->andWhere(['fail' => true]);
    }

    /**
     * Returns a job's status.
     *
     * @param array|false $payload
     * @return int
     */
    private function _status(array|false $payload): int
    {
        if (!$payload) {
            return self::STATUS_DONE;
        }

        if ($payload['fail']) {
            return self::STATUS_FAILED;
        }

        if (!$payload['timeUpdated']) {
            return self::STATUS_WAITING;
        }

        return self::STATUS_RESERVED;
    }

    /**
     * Acquires a lock and then executes the provided callback
     *
     * @param callable $callback
     * @param int|null $timeout
     * @param bool $throwException
     * @throws Exception
     */
    private function _lock(callable $callback, ?int $timeout = null, bool $throwException = true): void
    {
        $acquireLock = !$this->_locked;

        if ($acquireLock) {
            $channel = $this->channel();
            $mutexName = sprintf('%s::%s', __CLASS__, $channel);
            if (!$this->mutex->acquire($mutexName, $timeout ?? $this->mutexTimeout)) {
                if ($throwException) {
                    throw new Exception("Could not acquire a mutex lock for the queue ($channel).");
                }
                return;
            }
            $this->_locked = true;
        }

        try {
            $callback();
        } finally {
            if ($acquireLock) {
                $this->mutex->release($mutexName);
                $this->_locked = false;
            }
        }
    }

    /**
     * MySQL's text column can only hold 65535 bytes, so let's truncate if the
     * error message is longer than that.
     *
     * @param string $message
     * @return string
     */
    private function _truncateErrorMessage(string $message): string
    {
        if (strlen($message) > 65000 && Craft::$app->getDb()->getIsMysql()) {
            return substr($message, 0, 65000);
        }

        return $message;
    }
}
