<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql\mutations;

use Codeception\Stub\Expected;
use craft\elements\GlobalSet;
use craft\gql\resolvers\mutations\GlobalSet as GlobalSetResolver;
use craft\helpers\StringHelper;
use craft\test\TestCase;
use GraphQL\Type\Definition\ResolveInfo;

class ResolveGlobalSetMutationTest extends TestCase
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
     * Test saving global set.
     */
    public function testSaveGlobalSet()
    {
        $globalSetUid = StringHelper::UUID();
        $globalSetTitle = StringHelper::UUID();

        $arguments = ['title' => $globalSetTitle];

        $resolver = $this->make(GlobalSetResolver::class, [
            'getResolutionData' => Expected::once(new GlobalSet(['id' => 7, 'uid' => $globalSetUid])),
            'requireSchemaAction' => Expected::once(function($scope, $action) use ($globalSetUid) {
                $this->assertSame('globalsets.' . $globalSetUid, $scope);
                $this->assertSame($action, 'edit');
            }),
            'populateElementWithData' => Expected::once(function($element, $passedArguments) use ($arguments) {
                $this->assertSame($arguments, $passedArguments);
                return $element;
            }),
            'saveElement' => Expected::once(function($element) {
                return $element;
            })
        ]);

        $this->tester->mockCraftMethods('globals', [
            'getSetById' => Expected::once()
        ]);

        $resolver->saveGlobalSet(null, $arguments, null, $this->make(ResolveInfo::class));
    }
}
