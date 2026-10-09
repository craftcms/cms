<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Legacy\helpers;

use craft\helpers\ElementHelper;
use CraftCms\Yii2Adapter\Tests\TestCase;

class ElementHelperRootSourceKeyTest extends TestCase
{
    public function testRootSourceKeyReturnsRootSegment(): void
    {
        self::assertSame('foo', ElementHelper::rootSourceKey('foo'));
        self::assertSame('foo', ElementHelper::rootSourceKey('foo/bar'));
        self::assertSame('foo', ElementHelper::rootSourceKey('foo/bar/baz'));
    }
}
