<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql;

use Codeception\Test\Unit;
use Craft;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\Entry as GqlEntryType;
use craft\helpers\Json;
use craft\test\mockclasses\elements\ExampleElement;
use craft\test\mockclasses\gql\MockDirective;
use GraphQL\Type\Definition\ResolveInfo;

class DirectiveTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        // Make sure the mock directive is available in the entity registry
        $directiveName = MockDirective::getName();

        if (!GqlEntityRegistry::getEntity($directiveName)) {
            GqlEntityRegistry::createEntity($directiveName, MockDirective::getDirective());
        }
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
     * @dataProvider genericDirectiveDataProvider
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

    public function genericDirectiveDataProvider()
    {
        $name = MockDirective::getName();
        $noArgumentDirective = $this->_buildDirective($name);

        return [
            ['something', [$noArgumentDirective], 'mocksomething'],
            ['otherThing', [$noArgumentDirective, $noArgumentDirective], 'mockmockotherThing'],
            ['something', [], 'something'],
            ['dog', [$this->_buildDirective($name, ['prefix' => 'lazy'])], 'lazydog'],
            ['fox', [$this->_buildDirective($name, ['prefix' => 'brown']), $this->_buildDirective($name, ['prefix' => 'quick'])], 'quickbrownfox'],
            ['someText', [$this->_buildDirective($name, ['prefix' => 'brown']), $noArgumentDirective, $this->_buildDirective($name, ['prefix' => 'stuff'])], 'stuffmockbrownsomeText'],
        ];
    }
    /**
     * Build the JSON string to be used as a directive object
     * 
     * @param string $directiveName
     * @param array $arguments
     * @return string
     */
    private function _buildDirective(string $directiveName, array $arguments = [])
    {
        $directiveTemplate = '{"name": {"value": "%s"}, "arguments": [%s]}';
        $argumentTemplate = '{"name": {"value":"%s"}, "value": {"value": "%s"}}';

        $argumentList = [];
        foreach ($arguments as $key => $value) {
            $argumentList[] = sprintf($argumentTemplate, $key, $value);
        }

        return sprintf($directiveTemplate, $directiveName, implode(', ', $argumentList));
    }
}