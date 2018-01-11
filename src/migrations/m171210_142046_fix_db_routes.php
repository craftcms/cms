<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

/**
 * m171210_142046_fix_db_routes migration.
 */
class m171210_142046_fix_db_routes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $results = (new Query())
            ->select(['id', 'siteId', 'uriParts', 'template'])
            ->from(['{{%routes}}'])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->all();

        if (empty($results)) {
            return;
        }

        $routesService = Craft::$app->getRoutes();

        foreach ($results as $result) {
            $routesService->saveRoute(Json::decode($result['uriParts']), $result['template'], $result['siteId'], $result['id']);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m171210_142046_fix_db_routes cannot be reverted.\n";
        return false;
    }
}
