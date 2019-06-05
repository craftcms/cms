<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql;

use Codeception\Test\Unit;
use Craft;
use craft\fields\Date;
use craft\gql\directives\FormatDateTime;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\Entry as GqlEntryType;
use craft\helpers\Json;
use craft\test\mockclasses\elements\ExampleElement;
use craft\test\mockclasses\gql\MockDirective;
use DateTime;
use GraphQL\Type\Definition\ResolveInfo;

class DirectiveTest extends Unit
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

    public function _fixtures()
    {
    }

    // Tests
    // =========================================================================

    /**
     * Test if directives are being applied at all.
     *
     * @dataProvider directiveDataProvider
     *
     * @param string $in input string
     * @param array $directives an array of directive data as expected by GQL
     * @param string $result expected result
     */
    public function testDirectivesBeingApplied($in, array $directives, $result)
    {
        /** @var GqlEntryType $type */
        $type = $this->make(GqlEntryType::class);
        $element = new ExampleElement();
        $element->someField = $in;

        $fieldNodes = [Json::decode('{"directives":[' . implode(',', $directives) . ']}', false)];

        $resolveInfo = $this->make(ResolveInfo::class, [
            'fieldName' => 'someField',
            'fieldNodes' => $fieldNodes
        ]);

        $this->assertEquals($result, $type->resolveWithDirectives($element, [], null, $resolveInfo));
    }

    // Data Providers
    // =========================================================================

    public function directiveDataProvider()
    {
        $mockDirective = MockDirective::class;
        $formatDateTime = FormatDateTime::class;
        $dateTime = new DateTime('now');

        return [
            ['TestString', [$this->_buildDirective($mockDirective, ['prefix' => 'Foo'])], 'FooTestString'],
            ['TestString', [$this->_buildDirective($mockDirective, ['prefix' => 'Bar']), $this->_buildDirective($mockDirective, ['prefix' => 'Foo'])], 'FooBarTestString'],
            [$dateTime, [$this->_buildDirective($formatDateTime, ['format' => 'Y-m-d H:i:s'])], $dateTime->format('Y-m-d H:i:s')],
            [$dateTime, [$this->_buildDirective($formatDateTime, ['format' => DateTime::ATOM])], $dateTime->format(DateTime::ATOM)],
            [$dateTime, [$this->_buildDirective($formatDateTime, ['format' => DateTime::COOKIE])], $dateTime->format(DateTime::COOKIE)],
            [$dateTime,
                [$this->_buildDirective($formatDateTime, ['format' => DateTime::COOKIE, 'timezone' => 'America/New_York'])],
                $dateTime->setTimezone(new \DateTimeZone('America/New_York'))->format(DateTime::COOKIE)
            ],
            ['what time is it?', [$this->_buildDirective($formatDateTime, ['format' => DateTime::COOKIE])], 'what time is it?'],
        ];
    }

    /**
     * Build the JSON string to be used as a directive object
     * 
     * @param string $className
     * @param array $arguments
     * @return string
     */
    private function _buildDirective(string $className, array $arguments = [])
    {
        $this->_registerDirective($className);

        $directiveTemplate = '{"name": {"value": "%s"}, "arguments": [%s]}';
        $argumentTemplate = '{"name": {"value":"%s"}, "value": {"value": "%s"}}';

        $argumentList = [];
        foreach ($arguments as $key => $value) {
            $argumentList[] = sprintf($argumentTemplate, $key, addslashes($value));
        }

        return sprintf($directiveTemplate, $className::getName(), implode(', ', $argumentList));
    }

    /**
     * Register a directive by class name.
     *
     * @param $className
     */
    private function _registerDirective($className) {
        // Make sure the mock directive is available in the entity registry
        $directiveName = $className::getName();

        if (!GqlEntityRegistry::getEntity($directiveName)) {
            GqlEntityRegistry::createEntity($directiveName, $className::getDirective());
        }

    }
}