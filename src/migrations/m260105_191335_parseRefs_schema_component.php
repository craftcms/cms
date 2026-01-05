<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;

/**
 * m260105_191335_parseRefs_schema_component migration.
 */
class m260105_191335_parseRefs_schema_component extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $gql = Craft::$app->getGql();
        $schemas = $gql->getSchemas();

        foreach ($schemas as $schema) {
            if (in_array('directive:parseRefs', $schema->scope)) {
                continue;
            }

            $schema->scope[] = 'directive:parseRefs';
            $gql->saveSchema($schema);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        return true;
    }
}
