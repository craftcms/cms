<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\fixtures\elements;


use Craft;
use craft\base\ElementInterface;
use craft\elements\GlobalSet;

/**
 * Class GlobalSetFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Robuust digital | Bob Olde Hampsink <bob@robuust.digital>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2.0
 */
abstract class GlobalSetFixture extends BaseElementFixture
{
    /**
     * @inheritdoc
     */
    public function load()
    {
        parent::load();
        Craft::$app->getGlobals()->reset();
    }

    /**
     * @inheritdoc
     */
    public function unload()
    {
        parent::unload();
        Craft::$app->getGlobals()->reset();
    }

    /**
     * @inheritdoc
     */
    protected function createElement(): ElementInterface
    {
        return new GlobalSet();
    }

    /**
     * @inheritdoc
     */
    protected function saveElement(ElementInterface $element): bool
    {
        return Craft::$app->getGlobals()->saveSet($element);
    }
}
