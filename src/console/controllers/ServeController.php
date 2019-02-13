<?php

namespace craft\console\controllers;

use yii\console\controllers\ServeController as BaseServeController;

class ServeController extends BaseServeController {

    /**
     * @var string path or [path alias](guide:concept-aliases) to directory to serve
     */
    public $docroot = '@webroot';

}
