<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Legacy\helpers;

use craft\helpers\ElementHelper;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementHelper as LaravelElementHelper;
use CraftCms\Yii2Adapter\Tests\TestCase;
use Mockery;

class ElementHelperCompatibilityTest extends TestCase
{
    public function testSlugHelpersDelegateToLaravelImplementation(): void
    {
        self::assertSame(LaravelElementHelper::normalizeSlug('Audi S8 4E (2006-2010)'), ElementHelper::normalizeSlug('Audi S8 4E (2006-2010)'));
        self::assertSame(LaravelElementHelper::generateSlug('A-B-C'), ElementHelper::generateSlug('A-B-C'));
        self::assertTrue(ElementHelper::isTempSlug(ElementHelper::tempSlug()));
    }

    public function testUriFormatSlugDetectionDelegates(): void
    {
        self::assertTrue(ElementHelper::doesUriFormatHaveSlugTag('{slug}'));
        self::assertFalse(ElementHelper::doesUriFormatHaveSlugTag('{SLUG}'));
    }

    public function testCleanseQueryCriteriaRemovesUnsafeKeys(): void
    {
        $criteria = ElementHelper::cleanseQueryCriteria([
            'siteId' => 1,
            'where' => ['foo' => 'bar'],
            'orderBy' => 'title',
            'params' => ['x' => 'y'],
        ]);

        self::assertSame(['siteId' => 1], $criteria);
    }

    public function testSwapInProvisionalDraftsMutatesReferencedArrayForLegacyCallers(): void
    {
        $canonical = (object) ['id' => 1];
        $draft = (object) ['id' => 2];

        $drafts = Mockery::mock(Drafts::class);
        $drafts->shouldReceive('withProvisionalDrafts')
            ->once()
            ->with([$canonical])
            ->andReturn([$draft]);

        app()->instance(Drafts::class, $drafts);

        $elements = [$canonical];

        ElementHelper::swapInProvisionalDrafts($elements);

        self::assertSame($draft, $elements[0]);
    }
}
