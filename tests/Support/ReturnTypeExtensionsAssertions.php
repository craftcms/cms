<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support;

use PHPStan\Testing\TypeInferenceTestCase;

class ReturnTypeExtensionsAssertions extends TypeInferenceTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__.'/return-types.neon'];
    }

    public static function getComposerAutoloaderProjectPaths(): array
    {
        return [dirname(__DIR__, 2)];
    }

    public function assertReturnTypes(): void
    {
        $assertions = self::gatherAssertTypes(__DIR__.'/Fixtures/return-types.php');

        self::assertNotEmpty($assertions);

        foreach ($assertions as $assertion) {
            $this->assertFileAsserts(...$assertion);
        }
    }
}
