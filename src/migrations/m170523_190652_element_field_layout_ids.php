<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\MatrixBlock;
use craft\elements\Tag;

/**
 * m170523_190652_element_field_layout_ids migration.
 */
class m170523_190652_element_field_layout_ids extends Migration
{
    // Properties
    // -------------------------------------------------------------------------

    /**
     * @var bool Whether we're using a MySQL database
     */
    private $_isMysql;

    // Public Methods
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->_isMysql = $this->db->getIsMysql();

        // Add the elements.fieldLayoutId column + FK
        if (!$this->db->columnExists(Table::ELEMENTS, 'fieldLayoutId')) {
            $this->addColumn(Table::ELEMENTS, 'fieldLayoutId', $this->integer()->after('id'));
            $this->createIndex(null, Table::ELEMENTS, ['fieldLayoutId'], false);
            $this->addForeignKey(null, Table::ELEMENTS, ['fieldLayoutId'], Table::FIELDLAYOUTS, ['id'], 'SET NULL', null);
        }

        // Update the elements
        $this->_updateAssets();
        $this->_updateCategories();
        $this->_updateEntries();
        $this->_updateGlobalSets();
        $this->_updateMatrixBlocks();
        $this->_updateTags();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170523_190652_element_field_layout_ids cannot be reverted.\n";

        return false;
    }

    // Private Methods
    // -------------------------------------------------------------------------

    /**
     * Sets field layout IDs on all the assets.
     */
    private function _updateAssets()
    {
        if ($this->_isMysql) {
            $sql = 'update {{%elements}} [[e]]
inner join {{%assets}} [[a]] on [[a.id]] = [[e.id]]
inner join {{%volumes}} [[v]] on [[v.id]] = [[a.volumeId]]
set [[e.fieldLayoutId]] = [[v.fieldLayoutId]]
where [[e.type]] = :type';
        } else {
            $sql = 'update {{%elements}} [[e]]
set [[fieldLayoutId]] = [[v.fieldLayoutId]]
from {{%assets}} [[a]], {{%volumes}} [[v]]
where [[a.id]] = [[e.id]] and [[v.id]] = [[a.volumeId]] and [[e.type]] = :type';
        }

        $this->execute($sql, [':type' => Asset::class]);
    }

    /**
     * Sets field layout IDs on all the categories.
     */
    private function _updateCategories()
    {
        if ($this->_isMysql) {
            $sql = 'update {{%elements}} [[e]]
inner join {{%categories}} [[c]] on [[c.id]] = [[e.id]]
inner join {{%categorygroups}} [[cg]] on [[cg.id]] = [[c.groupId]]
set [[e.fieldLayoutId]] = [[cg.fieldLayoutId]]
where [[e.type]] = :type';
        } else {
            $sql = 'update {{%elements}} [[e]]
set [[fieldLayoutId]] = [[cg.fieldLayoutId]]
from {{%categories}} [[c]], {{%categorygroups}} [[cg]]
where [[c.id]] = [[e.id]] and [[cg.id]] = [[c.groupId]] and [[e.type]] = :type';
        }

        $this->execute($sql, [':type' => Category::class]);
    }

    /**
     * Sets field layout IDs on all the entries.
     */
    private function _updateEntries()
    {
        if ($this->_isMysql) {
            $sql = 'update {{%elements}} [[e]]
inner join {{%entries}} [[en]] on [[en.id]] = [[e.id]]
inner join {{%entrytypes}} [[et]] on [[et.id]] = [[en.typeId]]
set [[e.fieldLayoutId]] = [[et.fieldLayoutId]]
where [[e.type]] = :type';
        } else {
            $sql = 'update {{%elements}} [[e]]
set [[fieldLayoutId]] = [[et.fieldLayoutId]]
from {{%entries}} [[en]], {{%entrytypes}} [[et]]
where [[en.id]] = [[e.id]] and [[et.id]] = [[en.typeId]] and [[e.type]] = :type';
        }

        $this->execute($sql, [':type' => Entry::class]);
    }

    /**
     * Sets field layout IDs on all the global sets.
     */
    private function _updateGlobalSets()
    {
        if ($this->_isMysql) {
            $sql = 'update {{%elements}} [[e]]
inner join {{%globalsets}} [[g]] on [[g.id]] = [[e.id]]
set [[e.fieldLayoutId]] = [[g.fieldLayoutId]]
where [[e.type]] = :type';
        } else {
            $sql = 'update {{%elements}} [[e]]
set [[fieldLayoutId]] = [[g.fieldLayoutId]]
from {{%globalsets}} [[g]]
where [[g.id]] = [[e.id]] and [[e.type]] = :type';
        }

        $this->execute($sql, [':type' => GlobalSet::class]);
    }

    /**
     * Sets field layout IDs on all the Matrix blocks.
     */
    private function _updateMatrixBlocks()
    {
        if ($this->_isMysql) {
            $sql = 'update {{%elements}} [[e]]
inner join {{%matrixblocks}} [[mb]] on [[mb.id]] = [[e.id]]
inner join {{%matrixblocktypes}} [[mbt]] on [[mbt.id]] = [[mb.typeId]]
set [[e.fieldLayoutId]] = [[mbt.fieldLayoutId]]
where [[e.type]] = :type';
        } else {
            $sql = 'update {{%elements}} [[e]]
set [[fieldLayoutId]] = [[mbt.fieldLayoutId]]
from {{%matrixblocks}} [[mb]], {{%matrixblocktypes}} [[mbt]]
where [[mb.id]] = [[e.id]] and [[mbt.id]] = [[mb.typeId]] and [[e.type]] = :type';
        }

        $this->execute($sql, [':type' => MatrixBlock::class]);
    }

    /**
     * Sets field layout IDs on all the tags.
     */
    private function _updateTags()
    {
        if ($this->_isMysql) {
            $sql = 'update {{%elements}} [[e]]
inner join {{%tags}} [[t]] on [[t.id]] = [[e.id]]
inner join {{%taggroups}} [[tg]] on [[tg.id]] = [[t.groupId]]
set [[e.fieldLayoutId]] = [[tg.fieldLayoutId]]
where [[e.type]] = :type';
        } else {
            $sql = 'update {{%elements}} [[e]]
set [[fieldLayoutId]] = [[tg.fieldLayoutId]]
from {{%tags}} [[t]], {{%taggroups}} [[tg]]
where [[t.id]] = [[e.id]] and [[tg.id]] = [[t.groupId]] and [[e.type]] = :type';
        }

        $this->execute($sql, [':type' => Tag::class]);
    }
}
