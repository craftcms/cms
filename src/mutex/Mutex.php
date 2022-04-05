<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mutex;

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
     * @var YiiMutex|array|string The internal mutex driver to use.
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
            if (App::devMode()) {
                // Use NullMutex for Dev Mode, since they’re not really needed for development,
                // and partially to avoid Windows/Linux filesystem conflicts
                $this->mutex = NullMutex::class;
            } else {
                $this->mutex = App::mutexConfig();
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
