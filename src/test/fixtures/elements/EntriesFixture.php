<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\fixtures\elements;

use craft\elements\Entry;
use craft\errors\InvalidElementException;
use yii\db\Exception;
use Craft;

/**
 * Class EntriesFixture
 *
 * Credit to: https://github.com/robuust/craft-fixtures
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Robuust digital | Bob Olde Hampsink <bob@robuust.digital>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.1
 */
abstract class EntriesFixture extends ElementFixture
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritDoc
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
     * We load the section data only once we need it. This gives other fixtures
     * (see for e.g. craftunit\fixtures\SectionsFixture) the time to add their data.
     *
     * @throws InvalidElementException
     * @throws Exception
     */
    public function load(): void
    {
        parent::load();
    }

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        $sections = Craft::$app->getSections()->getAllSections();
        foreach ($sections as $section) {
            $this->sectionIds[$section->handle] = $section->id;
            $this->typeIds[$section->handle] = [];
            $types = Craft::$app->getSections()->getEntryTypesBySectionId($section->id);
            foreach ($types as $type) {
                $this->typeIds[$section->handle][$type->handle] = $type->id;
            }
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function isPrimaryKey(string $key): bool
    {
        return parent::isPrimaryKey($key) || in_array($key, ['sectionId', 'typeId', 'title']);
    }
}
