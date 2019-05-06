<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\services;


use Codeception\Test\Unit;
use Craft;
use craft\fields\BaseRelationField;
use craft\fields\Entries;
use craft\services\Relations;
use UnitTester;


/**
 * Unit tests for RelationTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class RelationsTest extends Unit
{
    /**
     * @var UnitTester $tester
     */
    protected $tester;

    /**
     * @var Relations $relations
     */
    protected $relations;

    /**
     * @var Entries $entriesField
     */
    protected $entriesField;

    public function _before()
    {
        parent::_before();
        $this->relations = Craft::$app->getRelations();
        $this->entriesField = $this->getEntriesField();
    }

    public function testStuff()
    {
        $this->assertSame('2', '2');
    }


    protected function getEntriesField() : Entries
    {
        return new Entries();
    }
}
