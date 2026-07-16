<?php

declare(strict_types=1);

namespace Workbench\App\TypeScript;

use Spatie\TypeScriptTransformer\Actions\TransformTypesAction;
use Spatie\TypeScriptTransformer\PhpNodes\PhpClassNode;
use Spatie\TypeScriptTransformer\References\ClassStringReference;
use Spatie\TypeScriptTransformer\Transformed\Transformed;
use Spatie\TypeScriptTransformer\TransformedProviders\TransformedProvider;
use Spatie\TypeScriptTransformer\Transformers\Transformer;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptReference;
use Spatie\TypeScriptTransformer\Visitor\Visitor;

class ClassListTransformedProvider implements TransformedProvider
{
    /**
     * @param  array<class-string>  $classes
     * @param  array<Transformer>  $transformers
     */
    public function __construct(
        private readonly array $classes,
        private readonly array $transformers,
        private readonly TransformTypesAction $transformTypes = new TransformTypesAction,
    ) {}

    public function provide(): array
    {
        $queue = $this->classes;
        $seen = [];
        $transformed = [];

        while ($class = array_shift($queue)) {
            $class = trim($class, '\\');

            if (isset($seen[$class])) {
                continue;
            }

            $seen[$class] = true;

            $classTransformed = $this->transformTypes->execute(
                $this->transformers,
                [PhpClassNode::fromClassString($class)],
            );

            foreach ($classTransformed as $item) {
                $transformed[] = $item;

                foreach ($this->referencedClasses($item) as $referencedClass) {
                    if (! isset($seen[$referencedClass])) {
                        $queue[] = $referencedClass;
                    }
                }
            }
        }

        return $transformed;
    }

    /**
     * @return array<class-string>
     */
    private function referencedClasses(Transformed $transformed): array
    {
        $classes = [];

        Visitor::create()
            ->before(function (TypeScriptReference $typeReference) use (&$classes): void {
                if (! $typeReference->reference instanceof ClassStringReference) {
                    return;
                }

                $class = $typeReference->reference->classString;

                if ($this->shouldTransformReference($class)) {
                    $classes[] = $class;
                }
            }, [TypeScriptReference::class])
            ->execute($transformed->getNode());

        return array_values(array_unique($classes));
    }

    private function shouldTransformReference(string $class): bool
    {
        if (! str_starts_with($class, 'CraftCms\\Cms\\')) {
            return false;
        }

        return class_exists($class) || enum_exists($class) || interface_exists($class);
    }
}
