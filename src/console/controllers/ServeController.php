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
 * Runs the PHP built-in web server.
 *
 * In order to access server from remote machines use 0.0.0.0:8000. That is especially useful when running server in
 * a virtual machine.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.10
 */
class ServeController extends BaseServeController
{
    use ControllerTrait;

    /**
     * @var string path or [path alias](guide:concept-aliases) to directory to serve
     */
    public $docroot = '@webroot';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->checkTty();
    }
}
