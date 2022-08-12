<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\log;

use Craft;
use Illuminate\Support\Collection;
use Monolog\Processor\ProcessorInterface;
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
     * @param string $key The key in the record to push context data
     * @param bool $dumpVars Whether to dump vars as a readable, multi-line string in the message
     */
    public function __construct(
        protected array $vars = [],
        protected string $key = 'context',
        protected bool $dumpVars = false,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function __invoke(array $record): array
    {
        if (Craft::$app->getConfig()->getGeneral()->storeUserIps) {
            $request = Craft::$app->getRequest();

            if ($request instanceof Request) {
                $record[$this->key]['ip'] = $request->getUserIP();
            }
        }

        $user = Craft::$app->has('user', true) ? Craft::$app->getUser() : null;
        if ($user && ($identity = $user->getIdentity(false))) {
            $record[$this->key]['userId'] = $identity->getId();
        }

        /** @var Session|null $session */
        $session = Craft::$app->has('session', true) ? Craft::$app->get('session') : null;

        if ($session && $session->getIsActive()) {
            $record[$this->key]['sessionId'] = $session->getId();
        }

        if (
            ($postPos = array_search('_POST', $this->vars, true)) !== false &&
            empty($GLOBALS['_POST']) &&
            !empty($body = file_get_contents('php://input'))
        ) {
            // Log the raw request body instead
            $this->vars = array_merge($this->vars);
            array_splice($this->vars, $postPos, 1);
            $record[$this->key]['body'] = $body;
        }

        if ($vars = $this->filterVars($this->vars)) {
            if ($this->dumpVars) {
                $record['message'] .= "\n" . $this->dumpVars($vars);
            } else {
                $record[$this->key]['vars'] = $vars;
            }
        }

        return $record;
    }

    protected function dumpVars(array $vars): string
    {
        return Collection::make($vars)
            ->map(function($value, $name) {
                return "\${$name} = " . VarDumper::dumpAsString($value);
            })
            ->join("\n\n");
    }

    protected function filterVars(array $vars = []): array
    {
        $filtered = Collection::make($GLOBALS)
            ->filter(fn($value, $key) => in_array($key, $vars, true));

        // Workaround for codeception testing until these gets addressed:
        // https://github.com/yiisoft/yii-core/issues/49
        // https://github.com/yiisoft/yii2/issues/15847
        if (Craft::$app) {
            $security = Craft::$app->getSecurity();
            $filtered = $filtered->map(fn($value, $key) => $security->redactIfSensitive($key, $value));
        }

        return $filtered->all();
    }
}
