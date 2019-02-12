<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\User;
use craft\fields\Assets;
use craft\helpers\Json;
use craft\services\Fields;
use craft\services\Matrix;

/**
 * m190218_143000_element_index_settings_uid migration.
 */
class m190218_143000_element_index_settings_uid extends Migration
{
   /**
     * @var array List of elements to search for.
     */
    private $_elements = [Entry::class => 'section', Asset::class => 'folder', User::class => 'group', Category::class => 'group'];

    /**
     * @var array List of element ID => UID pairs indexed by element class type.
     */
    private $_elementData = [];

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->_elementData[Entry::class] = (new Query())
            ->select(['id', 'uid'])
            ->from([Table::SECTIONS])
            ->pairs();

        $this->_elementData[User::class] = (new Query())
            ->select(['id', 'uid'])
            ->from([Table::USERGROUPS])
            ->pairs();

        $this->_elementData[Category::class] = (new Query())
            ->select(['id', 'uid'])
            ->from([Table::CATEGORYGROUPS])
            ->pairs();

        $this->_elementData[Asset::class] = (new Query())
            ->select(['id', 'uid'])
            ->from([Table::VOLUMEFOLDERS])
            ->where(['parentId' => null])
            ->pairs();

        $rows = (new Query())
            ->select('*')
            ->from([Table::ELEMENTINDEXSETTINGS])
            ->all();

        foreach ($rows as $row) {
            if (array_key_exists($row['type'], $this->_elements)) {
                $data = Json::decodeIfJson($row['settings']);

                if (is_array($data) && isset($data['sourceOrder'])) {
                    foreach ($data['sourceOrder'] as $key => $sourceOrder) {
                        if (isset($sourceOrder[1])) {
                            $data['sourceOrder'][$key][1] = $this->_normalizeSourceKey($sourceOrder[1], $row['type']);
                        }
                    }
                }

                if (is_array($data) && isset($data['sources'])) {
                    foreach ($data['sources'] as $key => $value) {
                        $newKey = $this->_normalizeSourceKey($key, $row['type']);
                        $data['sources'][$newKey] = $value;
                        unset($data['sources'][$key]);
                    }
                }

                $data = Json::encode($data);

                Craft::$app->getDb()->createCommand()
                    ->update(Table::ELEMENTINDEXSETTINGS, ['settings' => $data], ['id' => $row['id']], [], false)
                    ->execute();
            }
        }

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190218_143000_element_index_settings_uid cannot be reverted.\n";
        return false;
    }

    /**
     * Normalize a section:ID key to section:UID
     *
     * @param string $sourceKey
     * @param $elementClass
     * @return string
     */
    private function _normalizeSourceKey(string $sourceKey, $elementClass): string
    {
        if (empty($sourceKey) || strpos($sourceKey, $this->_elements[$elementClass] . ':') !== 0) {
            return $sourceKey;
        }

        $parts = explode(':', $sourceKey, 2);
        $id = $parts[1];

        if (!isset($this->_elementData[$elementClass][$id])) {
            return $sourceKey;
        }

        return $this->_elements[$elementClass] . ':' . $this->_elementData[$elementClass][$id];
    }
}
