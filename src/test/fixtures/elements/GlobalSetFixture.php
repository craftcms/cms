<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\fixtures\elements;


use craft\db\Table;
use craft\elements\GlobalSet;
use craft\records\GlobalSet as GlobalSetRecord;

/**
 * Class GlobalSetFixture
 *
 * Credit to: https://github.com/robuust/craft-fixtures
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Robuust digital | Bob Olde Hampsink <bob@robuust.digital>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2.0
 */
abstract class GlobalSetFixture extends ElementFixture
{
    /**
     * {@inheritdoc}
     */
    public $modelClass = GlobalSet::class;

    /**
     * @inheritdoc
     */
    public $tableName = Table::GLOBALSETS;

    /**
     * @var boolean
     */
    public $useActiveRecord = true;

    /**
     * @inheritdoc
     */
    public function load()
    {
        parent::load();

        if ($this->useActiveRecord) {
            // TODO: layouts?
            foreach ($this->data as $alias => $data) {
                $record = new GlobalSetRecord();
                $record->id = $data['id'];
                $record->name = $data['name'];
                $record->handle = $data['handle'];
                $record->uid = $data['uid'];
                $record->save();
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function isPrimaryKey(string $key): bool
    {
        return parent::isPrimaryKey($key) || $key === 'handle';
    }
}
