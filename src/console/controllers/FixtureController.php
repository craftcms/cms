<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use craft\events\DeleteElementEvent;
use craft\services\Elements;
use yii\base\Event;
use yii\console\controllers\FixtureController as BaseFixtureController;

/**
 * Allows you to manage test fixtures.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Robuust digital | Bob Olde Hampsink <bob@robuust.digital>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class FixtureController extends BaseFixtureController
{
    // Public functions
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function init()
    {
        parent::init();

        // Force a hard delete.
        Event::on(Elements::class, Elements::EVENT_BEFORE_DELETE_ELEMENT, function(DeleteElementEvent $event) {
            $event->hardDelete = true;
        });
    }
}
