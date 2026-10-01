<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Arch;

use CraftCms\Cms\Tests\Support\RequireSinceTagRule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RequireSinceTagRule>
 */
class RequireSinceTagRuleTest extends RuleTestCase
{
    private string $sourceDirectory = __DIR__.'/../Fixtures';

    protected function getRule(): RequireSinceTagRule
    {
        require_once __DIR__.'/../Fixtures/RequireSinceTagRule.php';

        return new RequireSinceTagRule(realpath($this->sourceDirectory));
    }

    public function test_requires_class_level_since_tags(): void
    {
        $this->analyse([__DIR__.'/../Fixtures/RequireSinceTagRule.php'], [
            ['Class MissingDocblock must have a non-empty @since tag in its docblock.', 7],
            ['Class MethodTagOnly must have a non-empty @since tag in its docblock.', 12],
            ['Class EmptySinceTag must have a non-empty @since tag in its docblock.', 23],
        ]);
    }

    public function test_ignores_classes_outside_source_directory(): void
    {
        $this->sourceDirectory = __DIR__.'/../../src';

        $this->analyse([__DIR__.'/../Fixtures/RequireSinceTagRule.php'], []);
    }
}
