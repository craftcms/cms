<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Db;
use craft\helpers\Json;

/**
 * m181029_130000_add_transforms_routes_to_config migration.
 */
class m181029_130000_add_transforms_routes_to_config extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $projectConfig = Craft::$app->getProjectConfig();

        // Don't make the same config changes twice
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);
        if (version_compare($schemaVersion, '3.1.5', '>=')) {
            return;
        }

        $this->_migrateTransforms();
        $this->_migrateRoutes();

        $this->dropTableIfExists('{{%routes}}');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m181029_130000_add_transforms_routes_to_config cannot be reverted.\n";
        return false;
    }

    // Private methods
    // =========================================================================

    /**
     * Migrate transforms to project config
     */
    private function _migrateTransforms()
    {
        $transformRows = (new Query())
            ->select([
                'name',
                'handle',
                'mode',
                'position',
                'width',
                'height',
                'format',
                'quality',
                'interlace',
                'uid',
            ])
            ->from([Table::ASSETTRANSFORMS])
            ->indexBy('uid')
            ->all();

        foreach ($transformRows as &$row) {
            unset($row['uid']);
            $row['width'] = (int)$row['width'] ?: null;
            $row['height'] = (int)$row['height'] ?: null;
            $row['quality'] = (int)$row['quality'] ?: null;
        }

        Craft::$app->getProjectConfig()->set('imageTransforms', $transformRows);
    }

    /**
     * Migrate routes to project config
     */
    private function _migrateRoutes()
    {
        $routes = (new Query())
            ->select([
                'uid',
                'siteId',
                'uriParts',
                'uriPattern',
                'template',
                'sortOrder',
            ])
            ->from(['{{%routes}}'])
            ->indexBy('uid')
            ->all();

        foreach ($routes as &$route) {
            $route['siteUid'] = $route['siteId'] ? Db::uidById(Table::SITES, $route['siteId']) : null;
            $route['uriParts'] = Json::decodeIfJson($route['uriParts']);
            unset($route['uid'], $route['siteId']);
            $route['sortOrder'] = (int)$route['sortOrder'];
        }

        Craft::$app->getProjectConfig()->set('routes', $routes);
    }
}
