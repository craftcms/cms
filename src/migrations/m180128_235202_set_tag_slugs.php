<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\elements\Tag;
use craft\helpers\Queue;
use craft\queue\jobs\ResaveElements;

/**
 * m180128_235202_set_tag_slugs migration.
 */
class m180128_235202_set_tag_slugs extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        Queue::push(new ResaveElements([
            'elementType' => Tag::class,
            'criteria' => [
                'slug' => ':empty:',
            ],
        ]));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180128_235202_set_tag_slugs cannot be reverted.\n";
        return false;
    }
}
