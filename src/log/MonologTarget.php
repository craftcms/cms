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
 * @property-read string $contextMessage
 */
class MonologTarget extends PsrTarget
{
    public $except = [
        PhpMessageSource::class . ':*',
        HttpException::class . ':404',
    ];
    public $extractExceptionTrace = true;

    protected string $name;
    protected int $maxFiles = 5;
    protected string $level = LogLevel::WARNING;
    protected ?FormatterInterface $formatter = null;
    protected ?ProcessorInterface $processor = null;
    protected $logger;

    public function __construct($config = [])
    {
        // We don't want to allow setting these props outside initialization,
        // as they won't be passed to the logger.
        $config = Collection::make($config)->filter(function($value, $key) use ($config) {
            $filterProps = ['name', 'maxFiles', 'level', 'processor', 'formatter'];

            if (in_array($key, $filterProps, true)) {
                $this->$key = $value;
                return false;
            }

            return true;
        })->all();

        parent::__construct($config);
    }

    public function init(): void
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        $this->formatter = $this->formatter ?? new LineFormatter(
            format: "%channel%.%level_name%: %message% %context% %extra%\n",
            allowInlineLineBreaks: $generalConfig->allowLogLineBreaks,
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
     */
    public function setLogger(LoggerInterface $logger): void
    {
        throw new InvalidConfigException('Logger may not be configured. Use `samdark\log\PsrTarget`.');
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
