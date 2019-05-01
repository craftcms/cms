<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\services;


use Codeception\Test\Unit;
use craft\services\Images;


/**
 * Unit tests for ImagesTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class ImagesTest extends Unit
{
    /**
     * @var \UnitTester $tester
     */
    protected $tester;

    /**
     * @var Images $gc
     */
    protected $images;

    public function _before()
    {
        parent::_before();
        $this->images = \Craft::$app->getImages();
    }
}
