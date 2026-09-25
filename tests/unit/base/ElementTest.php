<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\base;

use craft\base\Element;
use craft\models\FieldLayout;
use craft\test\TestCase;
use craft\web\twig\SecurityPolicy;
use Illuminate\Support\Collection;
use Twig\Sandbox\SecurityChecker;
use UnitTester;

/**
 * Unit tests for the Element class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class ElementTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * Elements nested within other iterables shouldn’t include their custom fields when the Twig sandbox iterates over
     * them, to avoid infinite recursion.
     *
     * @see https://github.com/craftcms/cms/issues/19004
     */
    public function testGetIteratorSkipsCustomFieldsWithinSandbox(): void
    {
        $checker = new SecurityChecker(new SecurityPolicy(allowedMethods: [
            Element::class => ['__toString'],
            Collection::class => ['__toString'],
        ]), true);

        // top-level elements still include their custom fields
        $element = $this->element();
        $checker->ensureToStringAllowed($element);
        self::assertSame(1, $element->fieldLayoutCalls);

        // nested elements don't
        $element = $this->element();
        $checker->ensureToStringAllowed(new Collection([$element]));
        self::assertSame(0, $element->fieldLayoutCalls);
    }

    private function element(): ElementTestElement
    {
        return new ElementTestElement();
    }
}

/**
 * An element for [[ElementTest]] that counts field layout lookups from [[getIterator()]].
 */
class ElementTestElement extends Element
{
    public int $fieldLayoutCalls = 0;

    public function getFieldLayout(): ?FieldLayout
    {
        // only count calls from getIterator()
        if (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] === 'getIterator') {
            $this->fieldLayoutCalls++;
        }
        return null;
    }
}
