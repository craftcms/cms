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
     * @var array Properties used to configure the Logger.
     */
    public const LOGGER_PROPS = [
        'allowLineBreaks',
        'name',
        'level',
        'maxFiles',
        'useMicrosecondTimestamps',
        'processor',
        'formatter',
    ];

    /**
     * @inheritdoc
     */
    public $except = [
        PhpMessageSource::class . ':*',
        HttpException::class . ':404',
    ];

    /**
     * @var bool
     */
    protected bool $allowLineBreaks;

    /**
     * @var string
     * @see Logger::$name
     */
    protected string $name;

    /**
     * @var string|null The PSR-3 log level to use.
     * Defaults to `LogLevel::DEBUG` if `devMode` is set to `true, otherwise `LogLevel::WARNING`.
     */
    protected ?string $level = null;

    /**
     * @var int The maximum number of files to keep in rotation.
     * @see RotatingFileHandler::$maxFiles
     */
    protected int $maxFiles = 5;

    /**
     * @see Logger::useMicrosecondTimestamps
     * @var bool
     */
    protected bool $useMicrosecondTimestamps = false;

    /**
     * @var FormatterInterface|null The Monolog formatter to use. Defaults to `LineFormatter`.
     */
    protected ?FormatterInterface $formatter = null;

    /**
     * @var ProcessorInterface|null The Monolog processor to use. Defaults to `PsrLogMessageProcessor`.
     */
    protected ?ProcessorInterface $processor = null;

    /**
     * @var Logger|null $logger
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
        $this->level = $this->level ?? (App::devMode() ? LogLevel::INFO : LogLevel::WARNING);
        $this->formatter = $this->formatter ?? new LineFormatter(
            allowInlineLineBreaks: $this->allowLineBreaks,
            ignoreEmptyContextAndExtra: true,
        );

        $this->processor = $this->processor ?? new PsrLogMessageProcessor();
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
     * Log additional request context.
     * @inheritDoc
     */
    public function export(): void
    {
        parent::export();

        $message = 'Request context';
        $vars = [];

        if ($this->allowLineBreaks) {
            $message .= "\n" . trim(parent::getContextMessage());
        } else {
            $vars = $this->logVars;
        }

        $this->logger->pushProcessor(new ContextProcessor(
            vars: $vars,
        ));

        // Log at default level, so it doesn't get filtered
        $this->logger->log($this->level, $message);
        $this->logger->popProcessor();
    }

    /**
     * Context is logged via {@see self::export} method, so it can be added using Monolog.
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
            ->useMicrosecondTimestamps($this->useMicrosecondTimestamps)
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
                    /** @phpstan-ignore-next-line */
                    $this->level,
                ))->setFormatter($this->formatter));
            }
        } else {
            $logger->pushHandler((new RotatingFileHandler(
                App::parseEnv(sprintf('@storage/logs/%s.log', $name)),
                $this->maxFiles,
                /** @phpstan-ignore-next-line */
                $this->level,
                filePermission: $generalConfig->defaultFileMode,
            ))->setFormatter($this->formatter));
        }

        return $logger;
    }
}
