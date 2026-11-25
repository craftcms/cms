<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;

/**
 * m260106_130629_directive_schema_components migration.
 */
class m260106_130629_directive_schema_components extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $gql = Craft::$app->getGql();
        $schemas = $gql->getSchemas();

        $components = ['directive:parseRefs'];

        if (!Craft::$app->getConfig()->getGeneral()->disableGraphqlTransformDirective) {
            $components[] = 'directive:transform';
        }

        foreach ($schemas as $schema) {
            // ignore empty schemas (e.g. the default Public Schema)
            if (empty($schema->scope)) {
                continue;
            }

            $updated = false;

            foreach ($components as $component) {
                if (!in_array($component, $schema->scope)) {
                    $schema->scope[] = $component;
                    $updated = true;
                }
            }

            if ($updated) {
                $gql->saveSchema($schema);
            }
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
