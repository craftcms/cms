<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\gql\mutations;

use craft\elements\Category;
use craft\gql\resolvers\mutations\Category as CategoryResolver;
use craft\test\TestCase;
use CraftCms\Cms\Support\Facades\Structures;
use UnitTester;

class StructureOperationMutationTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    protected function _before(): void
    {
    }

    protected function _after(): void
    {
    }

    /**
     * Test structure operations
     *
     * @dataProvider structureOperationDataProvider
     * @param array $elementProperties
     * @param array $arguments
     * @param string|null $requiredMethod
     * @param string|null $exception
     */
    public function testStructureOperations(array $elementProperties, array $arguments, ?string $requiredMethod = null, ?string $exception = null): void
    {
        $element = $this->make(Category::class, $elementProperties);

        $structuresMock = Structures::partialMock();

        $methods = [
            'prepend',
            'append',
            'prependToRoot',
            'appendToRoot',
            'moveBefore',
            'moveAfter',
        ];

        $structuresMock->shouldNotReceive(...$methods);

        if ($requiredMethod) {
            $structuresMock->shouldReceive($requiredMethod)->once()->andReturn(true);
        }

        $this->tester->mockCraftMethods('elements', [
            'getElementById' => fn($elementId) => $elementId > 0 ? new Category() : null,
        ]);

        if ($exception) {
            $this->expectExceptionMessage($exception);
        }

        $resolver = new CategoryResolver();

        $this->invokeMethod($resolver, 'performStructureOperations', [$element, $arguments]);

        Structures::clearResolvedInstances();
        app()->forgetInstance(\CraftCms\Cms\Structure\Structures::class);
    }

    public static function structureOperationDataProvider(): array
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
                'Unable to move element in a structure',
            ],
        ];
    }
}
