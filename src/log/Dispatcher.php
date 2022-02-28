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
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use yii\i18n\PhpMessageSource;
use yii\log\Target;
use yii\web\HttpException;

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
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        $this->targets = array_merge(App::defaultLogTargets(), $this->targets);
    }
}
