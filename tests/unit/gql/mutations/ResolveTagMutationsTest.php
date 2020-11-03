<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql\mutations;

use Codeception\Stub\Expected;
use craft\elements\Tag;
use craft\gql\resolvers\mutations\Tag as TagResolver;
use craft\models\TagGroup;
use craft\test\mockclasses\elements\MockElementQuery;
use craft\test\TestCase;
use GraphQL\Type\Definition\ResolveInfo;

class ResolveTagMutationsTest extends TestCase
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
     * Test saving tag with the passed arguments.
     *
     * @param $arguments
     * @param string $exception
     * @param bool $wrongGroup
     * @throws \Exception
     * @dataProvider saveTagDataProvider
     */
    public function testSaveTag($arguments, $exception = '', $wrongGroup = false)
    {
        if ($exception) {
            $this->expectExceptionMessage($exception);
        }

        $groupId = random_int(1, 1000);
        $tagId = random_int(1, 1000);

        // Make the tag throw an exception when cloned to prevent cloning during tests.
        $tag = $this->make(Tag::class, [
            'groupId' => $groupId + (int)$wrongGroup,
            'id' => $tagId
        ]);

        $this->tester->mockCraftMethods('elements', [
            'getElementById' => !empty($arguments['id']) && $arguments['id'] < 0 ? null : $tag,
            'createElementQuery' => (new MockElementQuery())->setReturnValues([$tag]),
            'saveElement' => Expected::once()
        ]);

        $resolver = $this->make(TagResolver::class, [
            'requireSchemaAction' => true,
            'getResolutionData' => new TagGroup(['id' => $groupId]),
            'saveElement' => function($element) use ($tagId) {
                $element->id = $element->id ?? $tagId;
                return $element;
            }
        ]);

        $resolver->saveTag(null, $arguments, null, $this->make(ResolveInfo::class));
    }

    /**
     * Test deleting a category checks for schema and calls the Element service.
     *
     * @throws \Exception
     */
    public function testDeleteTag()
    {
        $this->tester->mockCraftMethods('elements', [
            'getElementById' => Expected::once(new Tag(['groupId' => 2])),
            'deleteElementById' => Expected::once(true)
        ]);

        $resolver = $this->make(TagResolver::class, [
            'requireSchemaAction' => Expected::once(true)
        ]);

        $resolver->deleteTag(null, ['id' => 2], null, $this->make(ResolveInfo::class));
    }

    public function saveTagDataProvider()
    {
        return [
            [
                ['id' => 7]
            ],
            [
                ['id' => -7],
                'No such tag exists'
            ],
            [
                ['uid' => 'someUid']
            ],
            [
                ['title' => 'New tag']
            ],
            [
                ['uid' => 'someUid'],
                'Impossible to change the group of an existing tag',
                true
            ],
        ];
    }
}
