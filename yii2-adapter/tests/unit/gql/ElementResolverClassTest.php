<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\gql;

use craft\gql\base\ElementResolver;
use craft\gql\resolvers\elements\Category as CategoryResolver;
use craft\gql\types\elements\Asset as AssetType;
use craft\gql\types\elements\Category as CategoryType;
use craft\gql\types\elements\Element as ElementType;
use craft\gql\types\elements\Entry as EntryType;
use craft\test\TestCase;
use CraftCms\Cms\Gql\Resolvers\Elements\Asset as AssetResolver;
use CraftCms\Cms\Gql\Resolvers\Elements\Entry as EntryResolver;
use ReflectionMethod;

/**
 * Tests the extension points `prev`/`next` scoping relies on:
 * - craft\gql\types\elements\Element::elementResolverClass() defaults to null, and is
 *   overridden by the built-in types that expose `prev`/`next` fields.
 * - craft\gql\base\ElementResolver::prepareRootQuery() can reach a subclass's
 *   `prepareQuery()` even when that override is `protected` (not just `public`), so adding
 *   it didn't require loosening the abstract method's visibility.
 */
class ElementResolverClassTest extends TestCase
{
    public function testElementResolverClassDefaultsToNull(): void
    {
        self::assertNull($this->_elementResolverClass(ElementType::class));
    }

    /**
     * @dataProvider builtInResolverClassesDataProvider
     */
    public function testBuiltInTypesOverrideElementResolverClass(string $gqlType, string $expectedResolverClass): void
    {
        self::assertSame($expectedResolverClass, $this->_elementResolverClass($gqlType));
    }

    public static function builtInResolverClassesDataProvider(): array
    {
        return [
            [EntryType::class, EntryResolver::class],
            [AssetType::class, AssetResolver::class],
            [CategoryType::class, CategoryResolver::class],
        ];
    }

    /**
     * prepareRootQuery() is called from outside ElementResolver's own class hierarchy (by
     * Element::resolve()), so it only works for every resolver - including third-party ones
     * that declare `prepareQuery()` as `protected`, matching the abstract method's own
     * visibility - if it can reach a protected override without a visibility error.
     */
    public function testPrepareRootQueryReachesAProtectedPrepareQueryOverride(): void
    {
        $resolverClass = get_class(new class() extends ElementResolver {
            protected static function prepareQuery(mixed $source, array $arguments, ?string $fieldName = null): mixed
            {
                return ['source' => $source, 'arguments' => $arguments, 'fieldName' => $fieldName];
            }
        });

        $result = $resolverClass::prepareRootQuery(['orderBy' => 'id asc']);

        self::assertSame([
            'source' => null,
            'arguments' => ['orderBy' => 'id asc'],
            'fieldName' => null,
        ], $result);
    }

    private function _elementResolverClass(string $gqlType): ?string
    {
        $method = new ReflectionMethod($gqlType, 'elementResolverClass');
        $method->setAccessible(true);

        return $method->invoke(null);
    }
}
