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
use craft\records\GlobalSet as GlobalSetRecord;

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
    public function load(): void
    {
        parent::load();
        Craft::$app->getGlobals()->reset();
    }

    /**
     * @inheritdoc
     */
    public function unload(): void
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
        /** @var GlobalSet $element */
        if (!parent::saveElement($element)) {
            return false;
        }

        // Add the globalsets table row manually rather than going through Globals::saveSet(),
        // since the field layout should not be created/removed exclusively for this global set
        $record = new GlobalSetRecord();
        $record->id = $element->id;
        $record->uid = $element->uid;
        $record->name = $element->name;
        $record->handle = $element->handle;
        $record->fieldLayoutId = $element->fieldLayoutId;
        $record->save();

        return true;
    }
}
