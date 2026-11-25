<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

/**
 * m260401_155236_min_authors_setting migration.
 */
class m260401_155236_min_authors_setting extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // add maxAuthors to section
        $this->addColumn(
            Table::SECTIONS,
            'minAuthors',
            $this->smallInteger()->unsigned()->notNull()->defaultValue(1)->after('enableVersioning')
        );

        $entriesService = Craft::$app->getEntries();
        foreach ($entriesService->getAllSections() as $section) {
            $section->minAuthors = $section->maxAuthors === 0 ? 0 : 1;
            $entriesService->saveSection($section);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m260401_155236_min_authors_setting cannot be reverted.\n";
        return false;
    }
}
