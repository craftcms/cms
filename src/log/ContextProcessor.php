<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\log;

use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use Illuminate\Support\Collection;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use yii\base\InvalidArgumentException;
use yii\helpers\VarDumper;
use yii\web\Request;
use yii\web\Session;

/**
 * Class ContextProcessor
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class ContextProcessor implements ProcessorInterface
{
    /**
     * @param array $vars The global variables to include {@see \yii\log\Target::$logVars}
     * @param bool $dumpVars Whether to dump vars as a readable, multi-line string in the message
     */
    public function __construct(
        protected array $vars = [],
        protected bool $dumpVars = false,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        $data['environment'] = Craft::$app->env;

        if (Craft::$app->getConfig()->getGeneral()->storeUserIps) {
            $request = Craft::$app->getRequest();

            if ($request instanceof Request) {
                $data['ip'] = $request->getUserIP();
            }
        }

        $user = Craft::$app->has('user', true) ? Craft::$app->getUser() : null;
        if ($user && ($identity = $user->getIdentity(false))) {
            $data['userId'] = $identity->getId();
        }

        /** @var Session|null $session */
        $session = Craft::$app->has('session', true) ? Craft::$app->get('session') : null;

        if ($session && $session->getIsActive()) {
            $data['sessionId'] = $session->getId();
        }

        if (
            ($postPos = array_search('_POST', $this->vars, true)) !== false &&
            empty($GLOBALS['_POST']) &&
            !empty($body = file_get_contents('php://input'))
        ) {
            // Log the raw request body instead
            $this->vars = array_merge($this->vars);
            array_splice($this->vars, $postPos, 1);

            // Redact sensitive bits
            try {
                $decoded = Json::decode($body);
                if (is_array($decoded)) {
                    $decoded = Craft::$app->getSecurity()->redactIfSensitive('', $decoded);
                }
                $body = Json::encode($decoded);
            } catch (InvalidArgumentException) {
                // NBD
            }

            $data['body'] = $body;
        }

        $message = null;
        if ($vars = $this->filterVars($this->vars)) {
            if ($this->dumpVars) {
                $message = "\n" . $this->dumpVars($vars);
            } else {
                $data['vars'] = $vars;
            }
        }

        return new LogRecord(
            datetime: $record->datetime,
            channel: $record->channel,
            level: $record->level,
            message: $record->message . $message,
            context: $record->context + $data,
            extra: $record->extra,
            formatted: $record->formatted,
        );
    }

    protected function dumpVars(array $vars): string
    {
        return Collection::make($vars)
            ->map(fn($value, $name) => "\${$name} = " . VarDumper::dumpAsString($value))
            ->join("\n\n");
    }

    protected function filterVars(array $vars = []): array
    {
        $filtered = ArrayHelper::filter($GLOBALS, $vars);

        // Workaround for codeception testing until these gets addressed:
        // https://github.com/yiisoft/yii-core/issues/49
        // https://github.com/yiisoft/yii2/issues/15847
        if (Craft::$app) {
            $filtered = Craft::$app->getSecurity()->redactIfSensitive('', $filtered);
        }

        return $filtered;
    }
}
