<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use craft\console\ControllerTrait;
use yii\console\controllers\FixtureController as BaseFixtureController;

/**
 * Allows you to manage test fixtures.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Robuust digital | Bob Olde Hampsink <bob@robuust.digital>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2.0
 */
class FixtureController extends BaseFixtureController
{
    use ControllerTrait;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        $this->checkTty();
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // Make sure this isn't a root user
        if (!$this->checkRootUser()) {
            return false;
        }

        return parent::beforeAction($action);
    }
}
