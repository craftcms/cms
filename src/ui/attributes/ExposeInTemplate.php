<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\ui\attributes;

/**
 * Use to expose private/protected properties as variables directly
 * in a component template (`someProp` vs `this.someProp`). These
 * properties must be "accessible" (have a getter).
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ExposeInTemplate
{
    /**
     * @param string|null $name   The variable name to expose. Leave as null
     *                            to default to property name.
     * @param string|null $getter The getter method to use. Leave as null
     *                            to default to PropertyAccessor logic.
     */
    public function __construct(public ?string $name = null, public ?string $getter = null)
    {
    }
}
