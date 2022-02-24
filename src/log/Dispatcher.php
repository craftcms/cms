<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\log;

use craft\helpers\App;

/**
 * Class Dispatcher
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.0
 */
class Dispatcher extends \yii\log\Dispatcher
{
    /** @since 4.0.0 */
    public const LOGGER_WEB = 'web';

    /** @since 4.0.0 */
    public const LOGGER_WEB_404 = 'web-404';

    /** @since 4.0.0 */
    public const LOGGER_CONSOLE = 'console';

    /** @since 4.0.0 */
    public const LOGGER_QUEUE = 'queue';

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        $this->targets = array_merge(App::defaultLogTargets(), $this->targets);
    }
}
