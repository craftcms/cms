<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Nodes\Expressions;

use Twig\Node\Expression\TestExpression;

/**
 * A test that never converts the value it tests to a string, so the sandbox doesn’t check that value’s `__toString()`
 * method.
 *
 * Twig’s own `Twig\Node\Expression\Test\NonStringCoercingInputTest` is internal.
 *
 * @since 6.0.0
 */
class NonStringCoercingInputTest extends TestExpression
{
    /** @return list<string> */
    public function getStringCoercedChildNames(): array
    {
        return $this->hasNode('arguments') ? ['arguments'] : [];
    }
}
