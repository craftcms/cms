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
 * Class SectionsFixture
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class SectionsFixture extends ActiveFixture
{
    /**
     * @inheritdoc
     */
    public $dataFile = __DIR__ . '/data/sections.php';

    /**
     * @inheritdoc
     */
    public $modelClass = Section::class;

    /**
     * @inheritdoc
     */
    public $depends = [SectionSettingFixture::class, EntryTypeFixture::class];

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
        Db::delete(Table::SECTIONS_ENTRYTYPES);
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
