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
use yii\web\Request;
use yii\web\Session;

/**
 * Class LogProcessor
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class LogProcessor implements ProcessorInterface
{
    public function __construct(
        protected bool $includeUserIp = false,
        protected array $contextVars = [],
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

        if (
            ($postPos = array_search('_POST', $this->contextVars)) !== false &&
            empty($GLOBALS['_POST']) &&
            !empty($body = file_get_contents('php://input'))
        ) {
            // Log the raw request body instead
            $this->contextVars = array_merge($this->contextVars);
            array_splice($this->contextVars, $postPos, 1);
            $record['extra']['body'] = $body;
        }

        $record['extra']['vars'] = $this->filterVars($this->contextVars);

        return $record;
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
