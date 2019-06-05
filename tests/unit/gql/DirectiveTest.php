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
     * @dataProvider directiveDataProvider
     *
     * @param string $in input string
     * @param array $directives an array of directive data as expected by GQL
     * @param string $result expected result
     */
    public function testDirectivesBeingApplied($in, $directives, $result)
    {
        /** @var GqlEntryType $type */
        $type = $this->make(GqlEntryType::class);
        $element = new ExampleElement();
        $element->someField = $in;

        $fieldNodes = [];
        $node = Json::decode('{"directives":[]}', false);
        $node->directives = $directives;
        $fieldNodes[] = $node;

        $resolveInfo = $this->make(ResolveInfo::class, [
            'fieldName' => 'someField',
            'fieldNodes' => $fieldNodes
        ]);

        $this->assertEquals($type->resolveWithDirectives($element, [], null, $resolveInfo), $result);
    }


    // Data Providers
    // =========================================================================

    public function directiveDataProvider()
    {
        $noArgumentDirective = Json::decode('{"name": {"value": "' . MockDirective::getName() . '", "arguments": []}}', false);
        $directiveWithArguments = Json::decode('{"name": {"value": "' . MockDirective::getName() . '"}, "arguments": [{"name": {"value":"prefix"}, "value": {"value": "lazy"}}]}', false);

        return [
            ['something', [$noArgumentDirective], 'mocksomething'],
            ['something', [$noArgumentDirective, $noArgumentDirective], 'mockmocksomething'],
            ['something', [], 'something'],
            ['something', [$directiveWithArguments], 'lazysomething'],
            ['something', [$directiveWithArguments, $directiveWithArguments], 'lazylazysomething'],
            ['something', [$directiveWithArguments, $noArgumentDirective, $directiveWithArguments], 'lazymocklazysomething'],
            ['something', null, 'something'],
        ];
    }
}