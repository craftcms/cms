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
 * @since 3.2
 */
abstract class GlobalSetFixture extends ElementFixture
{
    // Public properties
    // =========================================================================

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
        if ($this->useActiveRecord) {
            parent::load();

            // TODO: layouts?
            foreach ($this->data as $alias => $data) {
                $record = new GlobalSetRecord();
                $record->id = $data['id'];
                $record->name = $data['name'];
                $record->handle = $data['handle'];
                $record->uid = $data['uid'];
                $record->save();
            }

            return;
        }

        $this->data = [];
        foreach ($this->getData() as $alias => $data) {
            // Pass in $data so we get an existing element
            $element = $this->getElement($data);

            foreach ($data as $handle => $value) {
                $element->$handle = $value;
            }

            if (!$this->saveElement($element)) {
                $this->getErrors($element);
            }

            $this->data[$alias] = array_merge($data, ['id' => $element->id]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function unload(): void
    {
        if ($this->useActiveRecord) {
            parent::unload();
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function isPrimaryKey(string $key): bool
    {
        return parent::isPrimaryKey($key) || $key === 'handle';
    }
}
