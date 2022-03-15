<?php

namespace craft\log;

use Craft;
use craft\helpers\App;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\ProcessorInterface;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use samdark\log\PsrTarget;
use yii\base\InvalidConfigException;
use yii\i18n\PhpMessageSource;
use yii\web\HttpException;

/**
 * Class MonologTarget
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @property-read string $contextMessage
 * @since 4.0.0
 */
class MonologTarget extends PsrTarget
{
    /**
     * @var bool|null $addTimestampToContext Defaults to `true` if `addTimestampToMessage` is `false`.
     * @see addTimestampToMessage
     * @inheritdoc
     */
    public $addTimestampToContext;

    /**
     * @inheritdoc
     */
    public $extractExceptionTrace = !YII_DEBUG;

    /**
     * @var bool
     */
    public bool $allowLineBreaks = YII_DEBUG;

    /**
     * @inheritdoc
     */
    public $except = [
        PhpMessageSource::class . ':*',
        HttpException::class . ':404',
    ];

    /**
     * @var array Properties used to configure the Logger.
     */
    public const LOGGER_PROPS = [
        'name',
        'maxFiles',
        'level',
        'processor',
        'formatter',
        'addTimestampToMessage',
    ];

    /**
     * @var string|null The PSR-3 log level to use.
     * Defaults to `LogLevel::DEBUG` if `devMode` is set to `true, otherwise `LogLevel::WARNING`.
     */
    protected ?string $level = null;

    /**
     * @var bool|null Whether to prepend a timestamp to the log message.
     * Defaults to `true` unless `CRAFT_STREAM_LOG` is set to `true`.
     */
    protected ?bool $addTimestampToMessage;

    /**
     * @var string
     * @see Logger::$name
     */
    protected string $name;

    /**
     * @var int The maximum number of files to keep in rotation.
     * @see RotatingFileHandler::$maxFiles
     */
    protected int $maxFiles = 5;

    /**
     * @var FormatterInterface|null The Monolog formatter to use. Defaults to `LineFormatter`.
     */
    protected ?FormatterInterface $formatter = null;

    /**
     * @var ProcessorInterface|null The Monolog processor to use. Defaults to `LogProcessor`.
     */
    protected ?ProcessorInterface $processor = null;

    /**
     * @var Logger $logger
     */
    protected $logger;

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function __set($name, $value): void
    {
        // Disallow setting logger props after logger is created.
        if (in_array($name, static::LOGGER_PROPS, true)) {
            if ($this->logger) {
                throw new InvalidConfigException("The property \"$name\" must be set before \"logger\".");
            }

            $this->$name = $value;

            return;
        }

        parent::__set($name, $value);
    }

    public function __get($name)
    {
        if (in_array($name, static::LOGGER_PROPS, true)) {
            return $this->$name;
        }

        return parent::__get($name);
    }

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $this->level = $this->level ?? (YII_DEBUG ? LogLevel::DEBUG : LogLevel::WARNING);
        $this->addTimestampToMessage = $this->addTimestampToMessage ?? !App::isStreamLog();
        $this->addTimestampToContext = $this->addTimestampToContext ?? !$this->addTimestampToMessage;
        $this->formatter = $this->formatter ?? new LineFormatter(
            format: $this->addTimestampToMessage ? null : "%channel%.%level_name%: %message% %context% %extra%\n",
            allowInlineLineBreaks: $this->allowLineBreaks,
            ignoreEmptyContextAndExtra: true,
        );

        $this->processor = $this->processor ?? new LogProcessor(
            includeUserIp: $generalConfig->storeUserIps,
            contextVars: $this->logVars,
        );

        $this->logger = $this->_createLogger($this->name);
    }

    /**
     * @throws InvalidConfigException
     * @inheritDoc
     */
    public function setLogger(LoggerInterface $logger): void
    {
        throw new InvalidConfigException('Logger may not be configured directly.');
    }

    /**
     * Vars are logged to context, not in the message.
     * @inheritDoc
     */
    protected function getContextMessage(): string
    {
        return '';
    }


    private function _createLogger(string $name): Logger
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $logger = (new Logger($name))
            ->pushProcessor(new PsrLogMessageProcessor())
            ->pushProcessor($this->processor);

        if (App::isStreamLog()) {
            $logger->pushHandler((new StreamHandler(
                'php://stderr',
                Logger::WARNING,
                bubble: false
            ))->setFormatter($this->formatter));

            // Don't pollute console request output
            if (!Craft::$app->getRequest()->getIsConsoleRequest()) {
                $logger->pushHandler((new StreamHandler(
                    'php://stdout',
                    $this->level,
                ))->setFormatter($this->formatter));
            }
        } else {
            $logger->pushHandler((new RotatingFileHandler(
                App::parseEnv(sprintf('@storage/logs/%s.log', $name)),
                $this->maxFiles,
                $this->level,
                filePermission: $generalConfig->defaultFileMode,
            ))->setFormatter($this->formatter));
        }

        return $logger;
    }
}
