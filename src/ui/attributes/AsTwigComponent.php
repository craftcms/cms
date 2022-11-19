<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\ui\attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class AsTwigComponent
{
    public function __construct(
        public string $name,
        public ?string $template = null,
        public bool $exposePublicProps = true,
        public string $attributesVar = 'attributes',
    ) {
    }

    /**
     * @return \ReflectionMethod[]
     */
    public static function preMountMethods(object $component): iterable
    {
        $methods = iterator_to_array(self::attributeMethodsFor(PreMount::class, $component));

        usort($methods, static function(\ReflectionMethod $a, \ReflectionMethod $b) {
            return $a->getAttributes(PreMount::class)[0]->newInstance()->priority <=> $b->getAttributes(PreMount::class)[0]->newInstance()->priority;
        });

        return array_reverse($methods);
    }

    /**
     * @return \ReflectionMethod[]
     * @internal
     *
     */
    public static function postMountMethods(object $component): iterable
    {
        $methods = iterator_to_array(self::attributeMethodsFor(PostMount::class, $component));

        usort($methods, static function(\ReflectionMethod $a, \ReflectionMethod $b) {
            return $a->getAttributes(PostMount::class)[0]->newInstance()->priority <=> $b->getAttributes(PostMount::class)[0]->newInstance()->priority;
        });

        return array_reverse($methods);
    }

    protected static function attributeMethodsFor(string $attribute, object $component): \Traversable
    {
        foreach ((new \ReflectionClass($component))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getAttributes($attribute)[0] ?? null) {
                yield $method;
            }
        }
    }
}
