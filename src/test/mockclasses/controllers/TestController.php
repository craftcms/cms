<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\mockclasses\controllers;

use craft\web\Controller;

/**
 * Class TestController
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class TestController extends Controller
{
    // Public Properties
    // =========================================================================

    /**
     * @var array
     */
    protected $allowAnonymous = ['allow-anonymous'];

    // Public Methods
    // =========================================================================

    /**
     *
     */
    public function actionNotAllowAnonymous()
    {
    }

    /**
     *
     */
    public function actionAllowAnonymous()
    {
    }
}
