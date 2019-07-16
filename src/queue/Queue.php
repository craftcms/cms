<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\queue;

use Craft;
use craft\db\Table;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use yii\base\Exception;
use yii\db\Query;
use yii\queue\cli\Signal;
use yii\queue\ExecEvent;
use yii\web\Response;

/**
 * Craft Queue
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 * @since 3.0
 */
class Queue extends \yii\queue\cli\Queue implements QueueInterface
{
    // Properties
    // =========================================================================

    /**
     * @see isFailed()
     */
    const STATUS_FAILED = 4;

    // Properties
    // =========================================================================

    /**
     * @var int timeout
     */
    public $mutexTimeout = 3;

    /**
     * @var string command class name
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

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

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
        Craft::$app->getDb()->createCommand()
            ->update(
                Table::QUEUE,
                [
                    'dateReserved' => null,
                    'timeUpdated' => null,
                    'progress' => 0,
                    'progressLabel' => null,
                    'attempt' => 0,
                    'fail' => false,
                    'dateFailed' => null,
                    'error' => null,
                ],
                ['id' => $id],
                [],
                false
            )
            ->execute();
    }

    /**
     * Re-adds all failed jobs to the queue
     */
    public function retryAll()
    {
        // Move expired messages into waiting list
        $this->_moveExpired();

        Craft::$app->getDb()->createCommand()
            ->update(
                Table::QUEUE,
                [
                    'dateReserved' => null,
                    'timeUpdated' => null,
                    'progress' => 0,
                    'progressLabel' => null,
                    'attempt' => 0,
                    'fail' => false,
                    'dateFailed' => null,
                    'error' => null,
                ],
                ['fail' => true],
                [],
                false
            )
            ->execute();
    }

    /**
     * @inheritdoc
     */
    public function release(string $id)
    {
        Craft::$app->getDb()->createCommand()
            ->delete(Table::QUEUE, ['id' => $id])
            ->execute();
    }

    /**
     * @inheritdoc
     */
    public function setProgress(int $progress, string $label = null)
    {
        $data = [
            'progress' => $progress,
            'timeUpdated' => time(),
        ];

        if ($label !== null) {
            $data['progressLabel'] = $label;
        }

        Craft::$app->getDb()->createCommand()
            ->update(Table::QUEUE, $data, ['id' => $this->_executingJobId], [], false)
            ->execute();
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
    public function getJobInfo(int $limit = null): array
    {
        // Move expired messages into waiting list
        $this->_moveExpired();

        $results = $this->_createJobQuery()
            ->select(['id', 'description', 'progress', 'progressLabel', 'timeUpdated', 'fail', 'error'])
            ->where('[[timePushed]] <= :time - [[delay]]', [':time' => time()])
            ->orderBy(['priority' => SORT_ASC, 'id' => SORT_ASC])
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
    public function handleError($id, $job, $ttr, $attempt, $error)
    {
        /** @var \Throwable $error */
        $this->_executingJobId = null;

        if (parent::handleError($id, $job, $ttr, $attempt, $error)) {
            // Log the exception
            Craft::$app->getErrorHandler()->logException($error);

            // Mark the job as failed
            Craft::$app->getDb()->createCommand()
                ->update(
                    Table::QUEUE,
                    [
                        'fail' => true,
                        'dateFailed' => Db::prepareDateForDb(new \DateTime()),
                        'error' => $error->getMessage(),
                    ],
                    ['id' => $id],
                    [],
                    false
                )
                ->execute();
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

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function pushMessage($message, $ttr, $delay, $priority)
    {
        $db = Craft::$app->getDb();
        $db->createCommand()
            ->insert(
                Table::QUEUE,
                [
                    'job' => $message,
                    'description' => $this->_jobDescription,
                    'timePushed' => time(),
                    'ttr' => $ttr,
                    'delay' => $delay,
                    'priority' => $priority ?: 1024,
                ],
                false)
            ->execute();

        return $db->getLastInsertID(Table::QUEUE);
    }

    /**
     * @return array|null The payload, or null if there aren't any jobs to reserve
     * @throws Exception in case it hasn't waited the lock
     */
    protected function reserve()
    {
        $mutex = Craft::$app->getMutex();

        if (!$mutex->acquire(__CLASS__, $this->mutexTimeout)) {
            throw new Exception('Has not waited the lock.');
        }

        // Move expired messages into waiting list
        $this->_moveExpired();

        // Reserve one message
        $payload = $this->_createJobQuery()
            ->where(['and', ['fail' => false, 'timeUpdated' => null], '[[timePushed]] <= :time - [[delay]]'], [':time' => time()])
            ->orderBy(['priority' => SORT_ASC, 'id' => SORT_ASC])
            ->limit(1)
            ->one();

        if (is_array($payload)) {
            $payload['dateReserved'] = new \DateTime();
            $payload['timeUpdated'] = $payload['dateReserved']->getTimestamp();
            $payload['attempt'] = (int)$payload['attempt'] + 1;
            Craft::$app->getDb()->createCommand()
                ->update(
                    Table::QUEUE,
                    [
                        'dateReserved' => Db::prepareDateForDb($payload['dateReserved']),
                        'timeUpdated' => $payload['timeUpdated'],
                        'attempt' => $payload['attempt']
                    ],
                    ['id' => $payload['id']],
                    [],
                    false
                )
                ->execute();
        }

        $mutex->release(__CLASS__);

        // pgsql
        if (is_array($payload) && is_resource($payload['job'])) {
            $payload['job'] = stream_get_contents($payload['job']);
        }

        return $payload;
    }

    // Private Methods
    // =========================================================================

    /**
     * Moves expired messages into waiting list.
     */
    private function _moveExpired()
    {
        if ($this->_reserveTime !== time()) {
            $this->_reserveTime = time();
            Craft::$app->getDb()->createCommand()
                ->update(
                    Table::QUEUE,
                    [
                        'dateReserved' => null,
                        'timeUpdated' => null,
                        'progress' => 0,
                        'progressLabel' => null,
                    ],
                    '[[timeUpdated]] < :time - [[ttr]]',
                    [':time' => $this->_reserveTime],
                    false
                )
                ->execute();
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
            ->from(Table::QUEUE);
    }

    /**
     * Returns a new query for waiting jobs.
     *
     * @return Query
     */
    private function _createWaitingJobQuery(): Query
    {
        return $this->_createJobQuery()
            ->where(['fail' => false, 'timeUpdated' => null])
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
            ->where(['fail' => false, 'timeUpdated' => null])
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
            ->where(['and', ['fail' => false], ['not', ['timeUpdated' => null]]]);
    }

    /**
     * Returns a new query for failed jobs.
     *
     * @return Query
     */
    private function _createFailedJobQuery(): Query
    {
        return $this->_createJobQuery()
            ->where(['fail' => true]);
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
}
