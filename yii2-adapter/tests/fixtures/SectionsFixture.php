<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\fixtures;

use Craft;
use craft\records\Section;
use craft\services\Entries;
use craft\test\ActiveFixture;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sections;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
        Sections::refreshSections();

        foreach ($this->entryTypeIds as $key => $entryTypeIds) {
            DB::table(Table::SECTIONS_ENTRYTYPES)
                ->insert(Collection::make($entryTypeIds)->map(function($id, int $i) use ($key) {
                    return [
                        'sectionId' => $this->ids[$key],
                        'typeId' => $id,
                        'sortOrder' => $i + 1,
                    ];
                })->all());
        }
    }

    public function unload(): void
    {
        parent::unload();
        DB::table(Table::SECTIONS_ENTRYTYPES)->delete();
        $this->entryTypeIds = [];
    }

    protected function loadData($file, $throwException = true)
    {
        $this->entryTypeIds = [];
        $data = parent::loadData($file, $throwException);

        foreach ($data as $key => &$row) {
            if (isset($row['entryTypes'])) {
                $this->entryTypeIds[$key] = Arr::pull($row, 'entryTypes', []);
            }
        }

        return $data;
    }
}
