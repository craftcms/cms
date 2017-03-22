<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\MatrixBlock;
use craft\elements\Tag;
use craft\elements\User;

/**
 * m170322_204706_element_field_layout_ids migration.
 */
class m170322_204706_element_field_layout_ids extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Add the elements.fieldLayoutId column + FK
        $this->addColumn('{{%elements}}', 'fieldLayoutId', $this->integer());
        $this->createIndex($this->db->getIndexName('{{%elements}}', 'fieldLayoutId', false, true), '{{%elements}}', 'fieldLayoutId', false);
        $this->addForeignKey($this->db->getForeignKeyName('{{%elements}}', 'fieldLayoutId'), '{{%elements}}', 'fieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL', null);

        // Populate assets' field layout IDs
        $this->execute('update {{%elements}} [[e]]
inner join {{%assets}} [[a]] on [[a.id]] = [[e.id]]
inner join {{%volumes}} [[v]] on [[v.id]] = [[a.volumeId]]
set [[e.fieldLayoutId]] = [[v.fieldLayoutId]]
where [[e.type]] = :type',
            [':type' => Asset::class]);

        // Populate categories' field layout IDs
        $this->execute('update {{%elements}} [[e]]
inner join {{%categories}} [[c]] on [[c.id]] = [[e.id]]
inner join {{%categorygroups}} [[cg]] on [[cg.id]] = [[c.groupId]]
set [[e.fieldLayoutId]] = [[cg.fieldLayoutId]]
where [[e.type]] = :type',
            [':type' => Category::class]);

        // Populate entries' field layout IDs
        $this->execute('update {{%elements}} [[e]]
inner join {{%entries}} [[en]] on [[en.id]] = [[e.id]]
inner join {{%entrytypes}} [[et]] on [[et.id]] = [[en.typeId]]
set [[e.fieldLayoutId]] = [[et.fieldLayoutId]]
where [[e.type]] = :type',
            [':type' => Entry::class]);

        // Populate global sets' field layout IDs
        $this->execute('update {{%elements}} [[e]]
inner join {{%globalsets}} [[g]] on [[g.id]] = [[e.id]]
set [[e.fieldLayoutId]] = [[g.fieldLayoutId]]
where [[e.type]] = :type',
            [':type' => GlobalSet::class]);

        // Populate Matrix blocks' field layout IDs
        $this->execute('update {{%elements}} [[e]]
inner join {{%matrixblocks}} [[mb]] on [[mb.id]] = [[e.id]]
inner join {{%matrixblocktypes}} [[mbt]] on [[mbt.id]] = [[mb.typeId]]
set [[e.fieldLayoutId]] = [[mbt.fieldLayoutId]]
where [[e.type]] = :type',
            [':type' => MatrixBlock::class]);

        // Populate tags' field layout IDs
        $this->execute('update {{%elements}} [[e]]
inner join {{%tags}} [[t]] on [[t.id]] = [[e.id]]
inner join {{%taggroups}} [[tg]] on [[tg.id]] = [[t.groupId]]
set [[e.fieldLayoutId]] = [[tg.fieldLayoutId]]
where [[e.type]] = :type',
            [':type' => Tag::class]);

        // Populate users' field layout IDs
        $userFieldLayoutId = (new Query)
            ->select(['id'])
            ->from(['{{%fieldlayouts}}'])
            ->where(['type' => User::class])
            ->scalar();

        if ($userFieldLayoutId !== false) {
            $this->update('{{%elements}}',
                ['fieldLayoutId' => $userFieldLayoutId],
                ['type' => User::class],
                [], false);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170322_204706_element_field_layout_ids cannot be reverted.\n";
        return false;
    }
}
