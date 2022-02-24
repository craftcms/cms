<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\log;

use Craft;
use craft\helpers\ArrayHelper;
use Monolog\Processor\ProcessorInterface;
use yii\base\InvalidConfigException;
use yii\helpers\VarDumper;
use yii\log\LogRuntimeException;
use yii\web\Request;
use yii\web\Session;

/**
 * Class StreamLogTarget
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class LogProcessor implements ProcessorInterface
{
    public function __construct(
        protected bool $includeUserIp = false,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function __invoke(array $record): array
    {
        if ($this->includeUserIp) {
            $request = Craft::$app->getRequest();

            if ($request instanceof Request) {
                $record['extra']['ip'] = $request->getUserIP();
            }
        }

        $user = Craft::$app->has('user', true) ? Craft::$app->getUser() : null;
        if ($user && ($identity = $user->getIdentity(false))) {
            $record['extra']['userId'] = $identity->getId();
        }

        /** @var Session|null $session */
        $session = Craft::$app->has('session', true) ? Craft::$app->get('session') : null;

        if ($session && $session->getIsActive()) {
            $record['extra']['sessionId'] = $session->getId();
        }

        return $record;
    }

    /**
     * Generates the context information to be logged.
     *
     * @return string the context information. If an empty string, it means no context information.
     * @see Target::getContextMessage()
     */
    // protected function getContextMessage(): string
    // {
    //     $result = [];
    //
    //     if (
    //         ($postPos = array_search('_POST', $this->logVars)) !== false &&
    //         empty($GLOBALS['_POST']) &&
    //         !empty($body = file_get_contents('php://input'))
    //     ) {
    //         // Log the raw request body instead
    //         $logVars = array_merge($this->logVars);
    //         array_splice($logVars, $postPos, 1);
    //         $result[] = "Request body: $body";
    //     } else {
    //         $logVars = $this->logVars;
    //     }
    //
    //     $context = ArrayHelper::filter($GLOBALS, $logVars);
    //
    //     // Workaround for codeception testing until these gets addressed:
    //     // https://github.com/yiisoft/yii-core/issues/49
    //     // https://github.com/yiisoft/yii2/issues/15847
    //     if (Craft::$app) {
    //         $security = Craft::$app->getSecurity();
    //
    //         foreach ($context as $key => $value) {
    //             $value = $security->redactIfSensitive($key, $value);
    //             $result[] = "\$$key = " . VarDumper::dumpAsString($value);
    //         }
    //     }
    //
    //     return implode("\n\n", $result);
    // }

    /**
     * @var string|null a string that should replace all newline characters
     * in a log message. Default is `null` for no replacement.
     */
    // public ?string $replaceNewline = null;

    /**
     * @var bool whether to disable the timestamp. The default is `false` which
     * will prepend every message with a timestamp created with
     * [yii\log\Target::getTime()].
     */
    // public bool $disableTimestamp = false;

    /**
     * @var bool whether to use flock() to lock/unlock the stream before/after
     * writing. This can be used to ensure that the stream is written by 2
     * processes simultaneously. Note though, that not all stream types support
     * locking. The default is `false`.
     */
    // public bool $enableLocking = false;

    /**
     * Writes a log message to the given target URL
     *
     * @throws InvalidConfigException If unable to open the stream for writing
     * @throws LogRuntimeException If unable to write to the log
     */
    // public function export(): void
    // {
    //     $text = implode("\n", array_map([$this, 'formatMessage'], $this->messages)) . "\n";
    //
    //     $fp = $this->getFp();
    //     if ($this->enableLocking) {
    //         @flock($fp, LOCK_EX);
    //     }
    //
    //     if (@fwrite($fp, $text) === false) {
    //         $error = error_get_last();
    //         throw new LogRuntimeException("Unable to export log!: {$error['message']}");
    //     }
    //
    //     if ($this->enableLocking) {
    //         @flock($fp, LOCK_UN);
    //     }
    //
    //     $this->closeFp();
    // }

    /**
     * @inheritdoc
     */
    // public function formatMessage($message): string
    // {
    //     $text = $this->prefixString . trim(parent::formatMessage($message));
    //     return !isset($this->replaceNewline) ?
    //         $text :
    //         str_replace("\n", $this->replaceNewline, $text);
    // }

    /**
     * @inheritdoc
     */
    // protected function getTime($timestamp): string
    // {
    //     return $this->disableTimestamp ? '' : parent::getTime($timestamp);
    // }
}
