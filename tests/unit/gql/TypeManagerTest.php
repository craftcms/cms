<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql;

use Codeception\Test\Unit;
use craft\events\DefineGqlTypeFieldsEvent;
use craft\gql\TypeManager;
use yii\base\Event;

class TypeManagerTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    /**
     * Test whether it's possible to modify fields
     *
     * @dataProvider fieldModificationDataProvider
     *
     * @param array $fields Array of fields
     * @param callable $callback Callback for modifications
     * @param string $result expected result
     */
    public function testFieldModification($fields, $callback, $result)
    {
        TypeManager::flush();
        Event::on(TypeManager::class, TypeManager::EVENT_DEFINE_GQL_TYPE_FIELDS, $callback);
        $fields = TypeManager::prepareFieldDefinitions($fields, 'someName');
        Event::off(TypeManager::class, TypeManager::EVENT_DEFINE_GQL_TYPE_FIELDS, $callback);

        $this->assertSame($fields, $result);
    }

    /**
     * Test whether the cache works.
     */
    public function testFieldCache()
    {
        TypeManager::flush();
        $cachedName = 'someName';
        $fields= ['ok'];

        TypeManager::prepareFieldDefinitions([], $cachedName);
        $this->assertNotSame($fields, TypeManager::prepareFieldDefinitions($fields, $cachedName));
        TypeManager::flush();
        $this->assertSame($fields, TypeManager::prepareFieldDefinitions($fields, $cachedName));
    }

    public function fieldModificationDataProvider()
    {
        return [
            [
                ['field' => 'something'],
                function (DefineGqlTypeFieldsEvent $event) {
                    $event->fields['field'] = 'otherThing';
                },
                ['field' => 'otherThing'],
            ],
            [
                ['field' => 'something'],
                function (DefineGqlTypeFieldsEvent $event) {
                    $event->fields['otherField'] = 'otherThing';
                },
                ['field' => 'something', 'otherField' => 'otherThing'],
            ],
            [
                ['field' => 'something', 'otherField' => 'otherThing'],
                function (DefineGqlTypeFieldsEvent $event) {
                    unset($event->fields['otherField']);
                },
                ['field' => 'something'],
            ],
        ];
    }
}
