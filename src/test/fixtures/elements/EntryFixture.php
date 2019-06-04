<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\fixtures\elements;

use Craft;
use craft\elements\Entry;

/**
 * Class EntryFixture
 *
 * Credit to: https://github.com/robuust/craft-fixtures
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Robuust digital | Bob Olde Hampsink <bob@robuust.digital>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
abstract class EntryFixture extends ElementFixture
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $modelClass = Entry::class;

    /**
     * @var array
     */
    public $sectionIds = [];

    /**
     * @var array
     */
    public $typeIds = [];

    // Public Methods
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function init()
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

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function isPrimaryKey(string $key): bool
    {
        return parent::isPrimaryKey($key) || in_array($key, ['sectionId', 'typeId', 'title']);
    }
}
