<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace craft\test\elementfixtures;


use craft\db\Query;
use craft\elements\Entry;
use craft\test\Craft;

/**
 * Unit tests for EntriesFixture
 *
 * Credit to: https://github.com/robuust/craft-fixtures
 *
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Robuust digital | Bob Olde Hampsink <bob@robuust.digital>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class EntriesFixture extends ElementFixture
{
    /**
     * {@inheritdoc}
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

    /**
     * We load the section data only once we need it. This gives other fixtures (see for e.g. craftunit\fixtures\SectionsFixture) the
     * time to add their data.
     *
     * @throws \yii\base\ErrorException
     */
    public function load(): void
    {
        $this->ensureDataExists();

        parent::load();
    }

    public function ensureDataExists() : bool
    {
        if (!$this->sectionIds || !$this->typeIds) {
            /** @var \craft\services\Section */
            $sectionService = \Craft::$app->getSections();

            // Get all section and type id's
            $sections = $sectionService->getAllSections();
            foreach ($sections as $section) {
                $this->sectionIds[$section->handle] = $section->id;
                $this->typeIds[$section->handle] = [];
                $types = $sectionService->getEntryTypesBySectionId($section->id);
                foreach ($types as $type) {
                    $this->typeIds[$section->handle][$type->handle] = $type->id;
                }
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function isPrimaryKey(string $key): bool
    {
        return $key === 'sectionId' || $key === 'typeId' || $key === 'title';
    }
}