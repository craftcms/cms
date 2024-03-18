<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\log;

use Craft;
use craft\helpers\App;
use Illuminate\Support\Collection;
use Psr\Log\LogLevel;

/**
 * Class Dispatcher
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class Dispatcher extends \yii\log\Dispatcher
{
    /** @since 4.0.0 */
    public const TARGET_WEB = 'web';

    /** @since 4.0.0 */
    public const TARGET_CONSOLE = 'console';

    /** @since 4.0.0 */
    public const TARGET_QUEUE = 'queue';

    /**
     * @var array Config to pass to each MonologTarget
     * @since 4.0.0
     */
    public array $monologTargetConfig = [];

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->targets = array_merge($this->getDefaultTargets()->all(), $this->targets);
    }

    /**
     * Gets the active default target, or one specified by key.
     *
     * @param string|null $key The target key to use (`web`, `console`, or `queue`).
     * @return MonologTarget|null
     * @since 5.0.0
     */
    public function getDefaultTarget(?string $key = null): ?MonologTarget
    {
        $defaultTargets = $this->getDefaultTargets();

        return $key === null
            ? $defaultTargets->first(fn(MonologTarget $target) => $target->enabled)
            : $defaultTargets->get($key);
    }

    /**
     * @return Collection<MonologTarget>
     */
    public function getDefaultTargets(): Collection
    {
        // Warning - Don't do anything that could cause something to get logged from here!
        // If the dispatcher is configured with flushInterval => 1, it could cause a PHP error if any log
        // targets haven’t been instantiated yet.

        $isConsoleRequest = Craft::$app->getRequest()->getIsConsoleRequest();

        // Only log console requests and web requests that aren't getAuthTimeout requests
        if (!$isConsoleRequest && !Craft::$app->getUser()->enableSession) {
            return Collection::make();
        }

        return Collection::make([
            static::TARGET_WEB,
            static::TARGET_CONSOLE,
            static::TARGET_QUEUE,
        ])->mapWithKeys(function($name) use ($isConsoleRequest) {
            $allowLineBreaks = (bool) (App::env('CRAFT_LOG_ALLOW_LINE_BREAKS') ?? App::devMode());
            $config = $this->monologTargetConfig + [
                'class' => MonologTarget::class,
                'name' => $name,
                'enabled' => match ($isConsoleRequest) {
                    true => $name === static::TARGET_CONSOLE,
                    false => $name === static::TARGET_WEB,
                },
                'extractExceptionTrace' => !App::devMode(),
                'allowLineBreaks' => $allowLineBreaks,
                'level' => App::devMode() ? LogLevel::INFO : LogLevel::WARNING,
                'logContext' => !Craft::$app->getRequest()->getIsConsoleRequest(),
            ];

            return [$name => Craft::createObject($config)];
        });
    }
}
