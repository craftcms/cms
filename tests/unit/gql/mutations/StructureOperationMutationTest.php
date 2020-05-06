<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql\mutations;

use Codeception\Stub\Expected;
use craft\elements\Category;
use craft\gql\resolvers\mutations\Category as CategoryResolver;
use craft\test\TestCase;

class StructureOperationMutationTest extends TestCase
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
     * Test structure operations
     *
     * @dataProvider structureOperationDataProvider
     */
    public function testStructureOperations($elementProperties, $arguments, $requiredMethod = '', $exception = '')
    {
        $element = $this->make(Category::class, $elementProperties);

        $methods = [
            'prepend' => Expected::never(),
            'append' => Expected::never(),
            'prependToRoot' => Expected::never(),
            'appendToRoot' => Expected::never(),
            'moveBefore' => Expected::never(),
            'moveAfter' => Expected::never(),
        ];

        if ($requiredMethod) {
            $methods[$requiredMethod] = Expected::once(true);
        }

        $this->tester->mockCraftMethods('structures', $methods);
        $this->tester->mockCraftMethods('elements', [
            'getElementById' => function ($elementId) {
                return $elementId > 0 ? new Category() : null ;
            }
        ]);

        if ($exception) {
            $this->expectExceptionMessage($exception);
        }

        $resolver = new CategoryResolver();

        $this->invokeMethod($resolver, 'performStructureOperations', [$element, $arguments]);
    }

    public function structureOperationDataProvider()
    {
        return [
            [
                ['structureId' => 2],
                ['prependTo' => 1],
                'prepend',
            ],
            [
                ['structureId' => 2],
                ['appendTo' => 1],
                'append',
            ],
            [
                ['structureId' => 2],
                ['prependToRoot' => true],
                'prependToRoot',
            ],
            [
                ['structureId' => 2],
                ['appendToRoot' => true],
                'appendToRoot',
            ],
            [
                ['structureId' => 2],
                ['insertBefore' => 1],
                'moveBefore',
            ],
            [
                ['structureId' => 2],
                ['insertAfter' => 1],
                'moveAfter',
            ],
            [
                ['structureId' => 2],
                ['prependTo' => -1],
                '',
                'Unable to move element in a structure'
            ],
        ];
    }
}
