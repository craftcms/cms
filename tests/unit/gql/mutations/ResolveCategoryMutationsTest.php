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
use craft\models\CategoryGroup;
use craft\test\mockclasses\elements\MockElementQuery;
use craft\test\TestCase;
use GraphQL\Type\Definition\ResolveInfo;

class ResolveCategoryMutationsTest extends TestCase
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
     * Test saving category with the passed arguments.
     *
     * @param $arguments
     * @param string $exception
     * @param bool $wrongGroup
     * @throws \Exception
     * @dataProvider saveCategoryDataProvider
     */
    public function testSaveCategory($arguments, $exception = '', $wrongGroup = false)
    {
        if ($exception) {
            $this->expectExceptionMessage($exception);
        }

        $groupId = random_int(1, 1000);
        $categoryId = random_int(1, 1000);

        $category = new Category([
            'groupId' => $groupId + (int)$wrongGroup,
            'id' => $categoryId
        ]);

        $this->tester->mockCraftMethods('elements', [
            'getElementById' => !empty($arguments['id']) && $arguments['id'] < 0 ? null : $category,
            'createElementQuery' => (new MockElementQuery())->setReturnValues([$category]),
            'saveElement' => $exception ? true : Expected::once(true),
        ]);

        $resolver = $this->make(CategoryResolver::class, [
            'requireSchemaAction' => true,
            'getResolutionData' => new CategoryGroup(['id' => $groupId]),
            'performStructureOperations' => true,
            'saveElement' => function($element) use ($categoryId) {
                $element->id = $element->id ?? $categoryId;
                return $element;
            }
        ]);

        $resolver->saveCategory(null, $arguments, null, $this->make(ResolveInfo::class));
    }

    /**
     * Test deleting a category checks for schema and calls the Element service.
     *
     * @throws \Exception
     */
    public function testDeleteCategory()
    {
        $this->tester->mockCraftMethods('elements', [
            'getElementById' => Expected::once(new Category(['groupId' => 2])),
            'deleteElementById' => Expected::once(true)
        ]);

        $resolver = $this->make(CategoryResolver::class, [
            'requireSchemaAction' => Expected::once(true)
        ]);

        $resolver->deleteCategory(null, ['id' => 2], null, $this->make(ResolveInfo::class));
    }

    public function saveCategoryDataProvider()
    {
        return [
            [
                ['id' => 9]
            ],
            [
                ['id' => -9],
                'No such category exists'
            ],
            [
                ['uid' => 'someUid']
            ],
            [
                ['title' => 'New category']
            ],
            [
                ['uid' => 'someUid'],
                'Impossible to change the group of an existing category',
                true
            ],
        ];
    }
}
