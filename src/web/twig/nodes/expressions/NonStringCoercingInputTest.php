<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodes\expressions;

use Twig\Node\Expression\TestExpression;

/**
 * A test that never converts the value it tests to a string, so the sandbox doesn’t check that value’s `__toString()`
 * method.
 *
 * Twig’s own `Twig\Node\Expression\Test\NonStringCoercingInputTest` is internal.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.3
 */
class NonStringCoercingInputTest extends TestExpression
{
    public function getStringCoercedChildNames(): array
    {
        return $this->hasNode('arguments') ? ['arguments'] : [];
    }
}
