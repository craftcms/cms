<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql;

use Codeception\Test\Unit;
use Craft;
use craft\events\DefineGqlTypeFieldsEvent;
use craft\gql\TypeManager;
use craft\services\Gql;
use yii\base\Event;

class TypeManagerTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    private ?Gql $_gqlService = null;

    protected function _before()
    {
        $this->_gqlService = Craft::$app->getGql();
    }

    protected function _after()
    {
    }

    /**
     * Test whether it's possible to modify fields
     *
     * @dataProvider fieldModificationDataProvider
     * @param array $fields Array of fields
     * @param callable $callback Callback for modifications
     * @param string $result expected result
     */
    public function testFieldModification($fields, $callback, $result)
    {
        $this->_gqlService->flushCaches();
        Event::on(TypeManager::class, TypeManager::EVENT_DEFINE_GQL_TYPE_FIELDS, $callback);
        $fields = $this->_gqlService->prepareFieldDefinitions($fields, 'someName');
        Event::off(TypeManager::class, TypeManager::EVENT_DEFINE_GQL_TYPE_FIELDS, $callback);

        self::assertSame($fields, $result);
    }

    /**
     * Test whether the cache works and flushing the GQL cache flushes it too.
     */
    public function testFieldCache()
    {
        $this->_gqlService->flushCaches();
        $cachedName = 'someName';
        $fields = ['ok'];

        $this->_gqlService->prepareFieldDefinitions([], $cachedName);
        self::assertNotSame($fields, $this->_gqlService->prepareFieldDefinitions($fields, $cachedName));
        Craft::$app->getGql()->flushCaches();
        self::assertSame($fields, $this->_gqlService->prepareFieldDefinitions($fields, $cachedName));
    }

    public function fieldModificationDataProvider()
    {
        return [
            [
                ['field' => 'something'],
                function(DefineGqlTypeFieldsEvent $event) {
                    $event->fields['field'] = 'otherThing';
                },
                ['field' => 'otherThing'],
            ],
            [
                ['field' => 'something'],
                function(DefineGqlTypeFieldsEvent $event) {
                    $event->fields['otherField'] = 'otherThing';
                },
                ['field' => 'something', 'otherField' => 'otherThing'],
            ],
            [
                ['field' => 'something', 'otherField' => 'otherThing'],
                function(DefineGqlTypeFieldsEvent $event) {
                    unset($event->fields['otherField']);
                },
                ['field' => 'something'],
            ],
        ];
    }
}
