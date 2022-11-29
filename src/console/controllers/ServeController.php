<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use craft\console\ControllerTrait;
use yii\console\controllers\ServeController as BaseServeController;

/**
 * Runs the built-in PHP web server.
 *
 * Use 0.0.0.0:8000 to access the server from remote machines, which is especially useful when running the server in
 * a virtual machine.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.10
 */
class ServeController extends BaseServeController
{
    use ControllerTrait;

    /**
     * @var string path or [path alias](https://craftcms.com/docs/4.x/config/#aliases) of the directory to serve.
     */
    public $docroot = '@webroot';

    /**
     * @inheritdoc
     */
    public $router = '@craftcms/bootstrap/router.php';

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
        // Make sure this isn’t a root user
        if (!$this->checkRootUser()) {
            return false;
        }

        return parent::beforeAction($action);
    }
}
