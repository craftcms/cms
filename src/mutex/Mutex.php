<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mutex;

use Craft;
use craft\helpers\App;
use yii\di\Instance;
use yii\mutex\Mutex as YiiMutex;

/**
 * @inheritdoc
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.30
 */
class Mutex extends YiiMutex
{
    use MutexTrait {
        init as private _init;
    }

    /**
     * @var YiiMutex|array|class-string<YiiMutex> The internal mutex driver to use.
     * @phpstan-var YiiMutex|array{class:class-string<YiiMutex>}|class-string<YiiMutex>
     *
     * This can be set from `config/app.php` like so:
     *
     * ```php
     * return [
     *     'components' => [
     *         'mutex' => [
     *             'mutex' => 'yii\redis\Mutex',
     *         ],
     *     ],
     * ];
     * ```
     */
    public YiiMutex|array|string $mutex;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->_init();

        if (!isset($this->mutex)) {
            if (Craft::$app->id !== 'craft-test' && Craft::$app->getIsInstalled()) {
                $this->mutex = App::dbMutexConfig();
            } else {
                $this->mutex = NullMutex::class;
            }
        }

        $this->mutex = Instance::ensure($this->mutex, YiiMutex::class);
    }

    /**
     * @inheritdoc
     */
    protected function acquireLock($name, $timeout = 0): bool
    {
        return $this->mutex->acquire($name, $timeout);
    }

    /**
     * @inheritdoc
     */
    protected function releaseLock($name): bool
    {
        return $this->mutex->release($name);
    }
}
