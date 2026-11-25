<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\fixtures;

use Craft;
use craft\db\Table;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\records\Section;
use craft\services\Entries;
use craft\test\ActiveFixture;

/**
 * Section used by NestedElementManagerTest.
 *
 * Mirrors {@see SectionsFixture}'s handling of the `entryTypes` data key, which isn't a column
 * on the `sections` table and needs to be bulk-inserted into `sections_entrytypes` separately.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class NemSectionFixture extends ActiveFixture
{
    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/nem-sections.php';

    /**
     * @inheritdoc
     */
    public $modelClass = Section::class;

    /**
     * @inheritdoc
     */
    public $depends = [NemSectionSettingFixture::class, NemEntryTypeFixture::class];

    private array $entryTypeIds = [];

    /**
     * @inheritdoc
     */
    public function load(): void
    {
        parent::load();

        $entriesService = new Entries();
        Craft::$app->set('entries', $entriesService);

        foreach ($this->entryTypeIds as $key => $entryTypeIds) {
            $data = [];
            foreach ($entryTypeIds as $i => $id) {
                $data[] = [$this->ids[$key], $id, $i + 1];
            }
            Db::batchInsert(
                Table::SECTIONS_ENTRYTYPES,
                ['sectionId', 'typeId', 'sortOrder'],
                $data,
            );
        }
    }

    public function unload(): void
    {
        parent::unload();
        Db::delete(Table::SECTIONS_ENTRYTYPES, ['sectionId' => array_values($this->ids)]);
        $this->entryTypeIds = [];
    }

    protected function loadData($file, $throwException = true)
    {
        $this->entryTypeIds = [];
        $data = parent::loadData($file, $throwException);

        foreach ($data as $key => &$row) {
            if (isset($row['entryTypes'])) {
                $this->entryTypeIds[$key] = ArrayHelper::remove($row, 'entryTypes') ?? [];
            }
        }

        return $data;
    }
}
