<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Codeception\Test\Unit;
use Craft;
use craft\db\Connection;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Db;
use craft\services\TemplateCaches;
use DateTime;
use Exception;

/**
 * Unit tests for the Template Caches service
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2
 */
class TemplateCachesTest extends Unit
{
    // Public Properties
    // =========================================================================

    /**
     * @var TemplateCaches
     */
    protected $templateCaches;

    /**
     * @var Connection
     */
    protected $db;

    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     * @throws Exception
     */
    public function testCachingDisabled()
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $generalConfig->enableTemplateCaching = false;
        $this->assertNull($this->templateCaches->getTemplateCache('one', false));
        $generalConfig->enableTemplateCaching = true;
    }

    /**
     * @throws Exception
     */
    public function testGlobalCache()
    {
        $this->assertNotNull($this->templateCaches->getTemplateCache('three', true));
    }

    /**
     * @throws Exception
     */
    public function testDeleteCacheById()
    {
        $cacheId = (new Query())
            ->select(['id'])
            ->from([Table::TEMPLATECACHES])
            ->limit(1)
            ->scalar();

        $this->assertNotFalse($cacheId);
        $this->assertTrue($this->templateCaches->deleteCacheById($cacheId));
        $this->assertFalse($this->templateCaches->deleteCacheById($cacheId));
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function _before()
    {
        parent::_before();
        $this->templateCaches = Craft::$app->getTemplateCaches();
        $this->db = Craft::$app->getDb();

        $this->db->createCommand()
            ->batchInsert(Table::TEMPLATECACHES, ['cacheKey', 'siteId', 'path', 'expiryDate', 'body'], [
                ['one', 1, 'site:path-one', Db::prepareDateForDb(new DateTime('+7 days')), 'body one'],
                ['two', 1, 'site:path-two', Db::prepareDateForDb(new DateTime('+60 days')), 'body two'],
                ['three', 1, null, Db::prepareDateForDb(new DateTime('+7 days')), 'body three'],
                ['four', 1, null, Db::prepareDateForDb(new DateTime('+60 days')), 'body four'],
                ['five', 1, 'site:path-five', Db::prepareDateForDb(new DateTime('-2 days')), 'body five'],
                ['six', 1, null, Db::prepareDateForDb(new DateTime('-2 days')), 'body six'],
            ], false)
            ->execute();
    }
}
