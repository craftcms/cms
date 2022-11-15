<?php

namespace craft\log;

use Craft;
use craft\helpers\App;
use Illuminate\Support\Collection;
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
     * @inheritdoc
     */
    public $except = [
        PhpMessageSource::class . ':*',
        HttpException::class . ':404',
    ];

    /**
     * @var bool Whether to log request context
     */
    public bool $logContext = true;

    /**
     * @var bool
     */
    protected bool $allowLineBreaks = false;

    /**
     * @var string
     * @see Logger::$name
     */
    protected string $name;

    /**
     * @var string The PSR-3 log level to use.
     * @phpstan-var LogLevel::*
     */
    protected string $level = LogLevel::WARNING;

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
     * @inheritdoc
     */
    public function init(): void
    {
        $this->formatter = $this->formatter ?? new LineFormatter(
            format: "%datetime% [%channel%.%level_name%] [%extra.yii_category%] %message% %context% %extra%\n",
            dateFormat: 'Y-m-d H:i:s',
            allowInlineLineBreaks: $this->allowLineBreaks,
            ignoreEmptyContextAndExtra: true,
        );
        $this->logger = $this->_createLogger($this->name);
    }

    /**
     * @return Logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function setLogger(Logger|LoggerInterface $logger): void
    {
        throw new InvalidConfigException('Logger may not be configured directly.');
    }

    /**
     * Log additional request context.
     * @inheritdoc
     */
    public function export(): void
    {
        $this->messages = $this->_filterMessagesByPsrLevel($this->messages, $this->level);
        parent::export();

        if (!$this->logContext || empty($this->messages)) {
            return;
        }

        $this->logger->pushProcessor(new ContextProcessor(
            vars: $this->logVars,
            dumpVars: $this->allowLineBreaks,
        ));

        // Log at default level, so it doesn't get filtered
        $this->logger->log($this->level, 'Request context:');
        $this->logger->popProcessor();
    }

    /**
     * Context is logged via {@see self::export} method, so it can be added using Monolog.
     * @inheritdoc
     */
    protected function getContextMessage(): string
    {
        return '';
    }

    /**
     * @param array $messages
     * @param string $level
     * @phpstan-param LogLevel::* $level
     * @return array
     */
    private function _filterMessagesByPsrLevel(array $messages, string $level): array
    {
        $levelMap = Collection::make((array) $this->getLevels());
        $monologLevel = Logger::toMonologLevel($level);
        $messages = Collection::make($messages)
            ->filter(function($message) use ($levelMap, $monologLevel) {
                $level = $message[1];
                $psrLevel = is_int($level) ? $levelMap->get($level) : $level;

                return Logger::toMonologLevel($psrLevel) >= $monologLevel;
            });

        return $messages->all();
    }

    private function _createLogger(string $name): Logger
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $logger = (new Logger($name))->useMicrosecondTimestamps($this->useMicrosecondTimestamps);

        if ($this->processor) {
            $logger->pushProcessor($this->processor);
        } else {
            $logger
                ->pushProcessor(new PsrLogMessageProcessor())
                ->pushProcessor(new MessageProcessor());
        }

        if (App::isStreamLog()) {
            $logger->pushHandler((new StreamHandler(
                'php://stderr',
                Logger::WARNING,
                bubble: false,
            ))->setFormatter($this->formatter));

            // Don't pollute console request output
            if (!Craft::$app->getRequest()->getIsConsoleRequest()) {
                $logger->pushHandler((new StreamHandler(
                    'php://stdout',
                    $this->level,
                    bubble: false,
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

    /**
     * @param string $name
     * @throws InvalidConfigException
     */
    public function setName(string $name): void
    {
        $this->_setLoggerProperty('name', $name);
    }

    /**
     * @param bool $allowLineBreaks
     * @throws InvalidConfigException
     */
    public function setAllowLineBreaks(bool $allowLineBreaks): void
    {
        $this->_setLoggerProperty('allowLineBreaks', $allowLineBreaks);
    }

    /**
     * @param string|null $level
     * @throws InvalidConfigException
     */
    public function setLevel(?string $level): void
    {
        $this->_setLoggerProperty('level', $level);
    }

    /**
     * @param int $maxFiles
     * @throws InvalidConfigException
     */
    public function setMaxFiles(int $maxFiles): void
    {
        $this->_setLoggerProperty('maxFiles', $maxFiles);
    }

    /**
     * @param bool $useMicrosecondTimestamps
     * @throws InvalidConfigException
     */
    public function setUseMicrosecondTimestamps(bool $useMicrosecondTimestamps): void
    {
        $this->_setLoggerProperty('useMicrosecondTimestamps', $useMicrosecondTimestamps);
    }

    /**
     * @param FormatterInterface|null $formatter
     * @throws InvalidConfigException
     */
    public function setFormatter(?FormatterInterface $formatter): void
    {
        $this->_setLoggerProperty('formatter', $formatter);
    }

    /**
     * @param ProcessorInterface|null $processor
     * @throws InvalidConfigException
     */
    public function setProcessor(?ProcessorInterface $processor): void
    {
        $this->_setLoggerProperty('processor', $processor);
    }

    /**
     * @throws InvalidConfigException
     */
    private function _setLoggerProperty(string $property, mixed $value): void
    {
        if (isset($this->logger)) {
            throw new InvalidConfigException("The property “{$property}” may not be set after logger is initialized.");
        }

        $this->$property = $value;
    }
}
