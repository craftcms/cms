<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\fixtures\elements;

use Craft;
use craft\base\ElementInterface;
use craft\elements\Entry;

/**
 * Class EntryFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Robuust digital | Bob Olde Hampsink <bob@robuust.digital>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2.0
 */
abstract class EntryFixture extends BaseElementFixture
{
    /**
     * @var array
     */
    public array $sectionIds = [];

    /**
     * @var array
     */
    public array $typeIds = [];

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        foreach (Craft::$app->getSections()->getAllSections() as $section) {
            $this->sectionIds[$section->handle] = $section->id;
            $this->typeIds[$section->handle] = [];

            foreach (Craft::$app->getSections()->getEntryTypesBySectionId($section->id) as $type) {
                $this->typeIds[$section->handle][$type->handle] = $type->id;
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function createElement(): ElementInterface
    {
        return new Entry();
    }
}
