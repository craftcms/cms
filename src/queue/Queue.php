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
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\db\Expression;
use yii\db\Query;
use yii\di\Instance;
use yii\mutex\Mutex;
use yii\queue\cli\Signal;
use yii\queue\ExecEvent;
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
    const STATUS_FAILED = 4;

    /**
     * @var Connection|array|string The database connection to use
     * @since 3.4.0
     */
    public $db = 'db';

    /**
     * @var Mutex|array|string The mutex component to use
     * @since 3.4.0
     */
    public $mutex = 'mutex';

    /**
     * @var int The time (in seconds) to wait for mutex locks to be released when attempting to reserve new jobs.
     */
    public $mutexTimeout = 3;

    /**
     * @var string The table name the queue is stored in.
     * @since 3.4.0
     */
    public $tableName = Table::QUEUE;

    /**
     * @var string The `channel` column value to the queue should use.
     * @since 3.4.0
     */
    public $channel = 'queue';

    /**
     * @inheritdoc
     */
    public $commandClass = Command::class;

    /**
     * @var string|null The description of the job being pushed into the queue
     */
    private $_jobDescription;

    /**
     * @var string|null The currently-executing job ID
     */
    private $_executingJobId;

    /**
     * @var int The timestamp the last job was reserved
     */
    private $_reserveTime;

    /**
     * @var bool Whether we're already listening for the web response
     */
    private $_listeningForResponse = false;

    /**
     * @var bool Whether a mutex lock has been acquired
     * @see _lock()
     */
    private $_locked = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->db = Instance::ensure($this->db, Connection::class);
        $this->mutex = Instance::ensure($this->mutex, Mutex::class);

        $this->on(self::EVENT_BEFORE_EXEC, function(ExecEvent $e) {
            $this->_executingJobId = $e->id;
        });

        $this->on(self::EVENT_AFTER_EXEC, function(ExecEvent $e) {
            $this->_executingJobId = null;
        });
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        while (!Signal::isExit() && ($payload = $this->reserve())) {
            if ($this->handleMessage($payload['id'], $payload['job'], $payload['ttr'], $payload['attempt'])) {
                $this->release($payload['id']);
            }
        }
    }

    /**
     * Listens to the queue and runs new jobs.
     *
     * @param integer $delay number of seconds for waiting new job.
     */
    public function listen(int $delay)
    {
        do {
            $this->run();
        } while (!$delay || sleep($delay) === 0);
    }

    /**
     * @param string $id of a job message
     * @return bool
     */
    public function isFailed(string $id): bool
    {
        return $this->status($id) === self::STATUS_FAILED;
    }

    /**
     *
     */
    public function status($id)
    {
        $payload = $this->_createJobQuery()
            ->select(['fail', 'timeUpdated'])
            // No need to use andWhere() here since we're fetching by ID
            ->where(['id' => $id])
            ->one();

        return $this->_status($payload);
    }

    /**
     * @inheritdoc
     */
    public function push($job)
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
    public function retry(string $id)
    {
        $this->_lock(function() use ($id) {
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
                'id' => $id,
            ], [], false, $this->db);
        });
    }

    /**
     * @inheritdoc
     */
    public function retryAll()
    {
        $this->_lock(function() {
            // Move expired messages into waiting list
            $this->_moveExpired();

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
                'channel' => $this->channel,
                'fail' => true,
            ], [], false, $this->db);
        });
    }

    /**
     * @inheritdoc
     */
    public function release(string $id)
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
    public function releaseAll()
    {
        $this->_lock(function() {
            Db::delete($this->tableName, [
                'channel' => $this->channel,
            ], [], $this->db);
        });
    }

    /**
     * @inheritdoc
     */
    public function setProgress(int $progress, string $label = null)
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
        $this->_moveExpired();

        return $this->_createWaitingJobQuery()->exists();
    }

    /**
     * @inheritdoc
     */
    public function getHasReservedJobs(): bool
    {
        // Move expired messages into waiting list
        $this->_moveExpired();

        return $this->_createReservedJobQuery()->exists();
    }

    /**
     * Returns the total number of waiting jobs
     *
     * @return int
     */
    public function getTotalWaiting(): int
    {
        // Move expired messages into waiting list
        $this->_moveExpired();

        return $this->_createWaitingJobQuery()->count();
    }

    /**
     * Returns the total number of delayed jobs
     *
     * @return int
     */
    public function getTotalDelayed(): int
    {
        // Move expired messages into waiting list
        $this->_moveExpired();

        return $this->_createDelayedJobQuery()->count();
    }

    /**
     * Returns the total number of reserved jobs
     *
     * @return int
     */
    public function getTotalReserved(): int
    {
        // Move expired messages into waiting list
        $this->_moveExpired();

        return $this->_createReservedJobQuery()->count();
    }

    /**
     * Returns the total number of failed jobs
     *
     * @return int
     */
    public function getTotalFailed(): int
    {
        // Move expired messages into waiting list
        $this->_moveExpired();

        return $this->_createFailedJobQuery()->count();
    }

    /**
     * @inheritdoc
     */
    public function getJobDetails(string $id): array
    {
        $result = (new Query())
            ->from($this->tableName)
            ->where(['id' => $id])
            ->one();

        if ($result === false) {
            throw new InvalidArgumentException("Invalid job ID: $id");
        }

        $formatter = Craft::$app->getFormatter();
        $job = $this->serializer->unserialize($this->_jobData($result['job']));

        return ArrayHelper::filterEmptyStringsFromArray([
            'status' => $this->_status($result),
            'error' => $result['error'] ?? '',
            'progress' => $result['progress'],
            'progressLabel' => $result['progressLabel'],
            'description' => $result['description'],
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
    public function getTotalJobs()
    {
        return $this->_createJobQuery()
            ->andWhere('[[timePushed]] <= :time - [[delay]]', [':time' => time()])
            ->count();
    }

    /**
     * @inheritdoc
     */
    public function getJobInfo(int $limit = null): array
    {
        // Move expired messages into waiting list
        $this->_moveExpired();

        // Set up the reserved jobs condition
        $reservedParams = [];
        $reservedCondition = $this->db->getQueryBuilder()->buildCondition([
            'and',
            ['fail' => false],
            ['not', ['timeUpdated' => null]],
        ], $reservedParams);

        $results = $this->_createJobQuery()
            ->select(['id', 'description', 'progress', 'progressLabel', 'timeUpdated', 'fail', 'error'])
            ->andWhere('[[timePushed]] <= :time - [[delay]]', [':time' => time()])
            ->orderBy(new Expression("CASE WHEN $reservedCondition THEN 1 ELSE 0 END DESC", $reservedParams))
            ->addOrderBy(['priority' => SORT_ASC, 'id' => SORT_ASC])
            ->limit($limit)
            ->all();

        $info = [];

        foreach ($results as $result) {
            $info[] = [
                'id' => $result['id'],
                'status' => $this->_status($result),
                'progress' => (int)$result['progress'],
                'progressLabel' => $result['progressLabel'],
                'description' => $result['description'],
                'error' => $result['error'],
            ];
        }

        return $info;
    }

    /**
     * @inheritdoc
     */
    public function handleError(ExecEvent $event)
    {
        $this->_executingJobId = null;

        // Have we given up?
        if (parent::handleError($event)) {
            // Mark the job as failed
            $this->_lock(function() use ($event) {
                Db::update($this->tableName, [
                    'fail' => true,
                    'dateFailed' => Db::prepareDateForDb(new \DateTime()),
                    'error' => $event->error ? $event->error->getMessage() : null,
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
    public function handleResponse()
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
        $url = Json::encode(UrlHelper::actionUrl('queue/run'));
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
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        $data = [
            'job' => $message,
            'description' => $this->_jobDescription,
            'timePushed' => time(),
            'ttr' => $ttr,
            'delay' => $delay,
            'priority' => $priority ?: 1024,
        ];

        // todo: remove this check after the next breakpoint
        if ($this->db->columnExists($this->tableName, 'channel')) {
            $data['channel'] = $this->channel;
        }

        Db::insert($this->tableName, $data, false, $this->db);
        return $this->db->getLastInsertID($this->tableName);
    }

    /**
     * @return array|null The payload, or null if there aren't any jobs to reserve
     * @throws Exception in case it hasn't waited the lock
     */
    protected function reserve()
    {
        $payload = null;

        $this->_lock(function() use (&$payload) {
            // Move expired messages into waiting list
            $this->_moveExpired();

            // Reserve one message
            $payload = $this->_createJobQuery()
                ->andWhere(['and', ['fail' => false, 'timeUpdated' => null], '[[timePushed]] <= :time - [[delay]]'], [':time' => time()])
                ->orderBy(['priority' => SORT_ASC, 'id' => SORT_ASC])
                ->limit(1)
                ->one();

            if (is_array($payload)) {
                $payload['dateReserved'] = new \DateTime();
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
    private function _jobData($job)
    {
        if (is_resource($job)) {
            $job = stream_get_contents($job);

            if (is_string($job) && strpos($job, 'x') === 0) {
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
     */
    private function _moveExpired()
    {
        if ($this->_reserveTime !== time()) {
            $this->_lock(function() {
                $this->_reserveTime = time();

                $expiredIds = (new Query())
                    ->select(['id'])
                    ->from([$this->tableName])
                    ->where([
                        'and',
                        [
                            'channel' => $this->channel,
                            'fail' => false,
                        ],
                        '[[timeUpdated]] < :time - [[ttr]]',
                    ], [
                        ':time' => $this->_reserveTime,
                    ])
                    ->column($this->db);

                if (!empty($expiredIds)) {
                    Db::update($this->tableName, [
                        'dateReserved' => null,
                        'timeUpdated' => null,
                        'progress' => 0,
                        'progressLabel' => null,
                    ], ['id' => $expiredIds], [], false, $this->db);
                }
            });
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
            ->where(['channel' => $this->channel]);
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
    private function _status($payload): int
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
     * @return void
     * @throws Exception
     */
    private function _lock(callable $callback): void
    {
        if ($acquireLock = !$this->_locked) {
            if (!$this->mutex->acquire(__CLASS__ . "::$this->channel", $this->mutexTimeout)) {
                throw new Exception("Could not acquire a mutex lock for the queue ($this->channel).");
            }
            $this->_locked = true;
        }

        $callback();

        if ($acquireLock) {
            $this->mutex->release(__CLASS__ . "::$this->channel");
            $this->_locked = false;
        }
    }
}
