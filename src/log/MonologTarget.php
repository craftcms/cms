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
    public string $name;
    public int $maxFiles = 5;
    public string $level = LogLevel::WARNING;
    public ?FormatterInterface $formatter = null;
    public ?ProcessorInterface $processor = null;

    protected $logger;

    public $except = [
        PhpMessageSource::class . ':*',
        HttpException::class . ':404',
    ];

    public function init(): void
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        $this->formatter = $this->formatter ?? new LineFormatter(
            format: "%channel%.%level_name%: %message% %context% %extra%\n",
            allowInlineLineBreaks: $generalConfig->allowLineBreaksInLogs,
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
        throw new InvalidConfigException('Logger may not be manually configured.');
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
        $logger = (new Logger($name))->pushProcessor($this->processor);

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
