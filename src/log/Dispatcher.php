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
    /** @since 3.6.9 */
    const TARGET_FILE = '__file__';

    /** @since 3.6.9 */
    const TARGET_STDOUT = '__stdout__';

    /** @since 3.6.9 */
    const TARGET_STDERR = '__stderr__';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->targets = array_merge($this->targets, App::defaultLogTargets());
    }
}
