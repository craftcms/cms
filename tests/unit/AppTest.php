<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */
namespace craftunit;

use Codeception\Test\Unit;
use Craft;
use craft\db\MigrationManager;
use craft\feeds\Feeds;
use craft\i18n\Locale;
use craft\queue\Queue;
use craft\services\Api;
use craft\services\AssetIndexer;
use craft\services\Assets;
use craft\services\AssetTransforms;
use craft\services\Categories;
use craft\services\Composer;
use craft\services\Config;
use craft\services\Content;
use craft\services\Dashboard;
use craft\services\Deprecator;
use craft\services\ElementIndexes;
use craft\services\Elements;
use craft\services\Entries;
use craft\services\EntryRevisions;
use craft\services\Globals;
use craft\services\Images;
use craft\services\Matrix;
use craft\services\Path;
use craft\services\Plugins;
use craft\services\PluginStore;
use craft\services\Relations;
use craft\services\Routes;
use craft\services\Search;
use craft\services\Sections;
use craft\services\Sites;
use craft\services\Structures;
use craft\services\SystemMessages;
use craft\services\SystemSettings;
use craft\services\Tags;
use craft\services\TemplateCaches;
use craft\services\Tokens;
use craft\services\Updates;
use craft\services\UserGroups;
use craft\services\UserPermissions;
use craft\services\Users;
use craft\services\Utilities;
use craft\services\Volumes;
use UnitTester;
use yii\base\InvalidConfigException;
use yii\mutex\Mutex;

/**
 * Unit tests for AppTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class AppTest extends Unit
{
    /**
     * @var UnitTester
     */
    public $tester;

    /**
     * @param $instance
     * @param $maps
     * @throws InvalidConfigException
     * @dataProvider craftAppGetMethods
     */
    public function testCraftAppGetMethods($instance, $map)
    {
        $func = $map[0];
        $this->assertInstanceOf($instance, Craft::$app->$func());
        $this->assertInstanceOf($instance, Craft::$app->get($map[1]));
        // http://www.php.net/manual/en/language.variables.variable.php#example-107
        $this->assertInstanceOf($instance, Craft::$app->{$map[1]});



    }
    public function craftAppGetMethods()
    {
        return [
            [Api::class, ['getApi', 'api']],
            [Assets::class, ['getAssets', 'assets']],
            [AssetIndexer::class, ['getAssetIndexer', 'assetIndexer']],
            [AssetTransforms::class, ['getAssetTransforms', 'assetTransforms']],
            [Categories::class, ['getCategories', 'categories']],
            [Composer::class, ['getComposer', 'composer']],
            [Config::class, ['getConfig', 'config']],
            [Content::class, ['getContent', 'content']],
            [MigrationManager::class, ['getContentMigrator', 'contentMigrator']],
            [Dashboard::class, ['getDashboard', 'dashboard']],
            [Deprecator::class, ['getDeprecator', 'deprecator']],
            [ElementIndexes::class, ['getElementIndexes', 'elementIndexes']],
            [Elements::class, ['getElements', 'elements']],
            [SystemMessages::class, ['getSystemMessages', 'systemMessages']],
            [Entries::class, ['getEntries', 'entries']],
            [EntryRevisions::class, ['getEntryRevisions', 'entryRevisions']],
            [Feeds::class, ['getFeeds', 'feeds']],
            [Globals::class, ['getGlobals', 'globals']],
            [Images::class, ['getImages', 'images']],
            [Locale::class, ['getLocale', 'locale']],
            // [Mailer::class, ['getMailer', 'mailer']],
            [Matrix::class, ['getMatrix', 'matrix']],
            [MigrationManager::class, ['getMigrator', 'migrator']],
            [Mutex::class, ['getMutex', 'mutex']],
            [Path::class, ['getPath', 'path']],
            [Plugins::class, ['getPlugins', 'plugins']],
            [PluginStore::class, ['getPluginStore', 'pluginStore']],
            [Queue::class, ['getQueue', 'queue']],
            [Relations::class, ['getRelations', 'relations']],
            [Routes::class, ['getRoutes', 'routes']],
            [Search::class, ['getSearch', 'search']],
            [Sections::class, ['getSections', 'sections']],
            [Sites::class, ['getSites', 'sites']],
            [Structures::class, ['getStructures', 'structures']],
            [SystemSettings::class, ['getSystemSettings', 'systemSettings']],
            [Tags::class, ['getTags', 'tags']],
            [TemplateCaches::class, ['getTemplateCaches', 'templateCaches']],
            [Tokens::class, ['getTokens', 'tokens']],
            [Updates::class, ['getUpdates', 'updates']],
            [UserGroups::class, ['getUserGroups', 'userGroups']],
            [UserPermissions::class, ['getUserPermissions', 'userPermissions']],
            [Users::class, ['getUsers', 'users']],
            [Utilities::class, ['getUtilities', 'utilities']],
            [Volumes::class, ['getVolumes', 'volumes']],
        ];
    }
}