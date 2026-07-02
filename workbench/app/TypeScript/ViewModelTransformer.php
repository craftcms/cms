<?php

declare(strict_types=1);

namespace Workbench\App\TypeScript;

use CraftCms\Cms\Http\ViewModels\ViewModel;
use ReflectionMethod;
use Spatie\TypeScriptTransformer\Data\TransformationContext;
use Spatie\TypeScriptTransformer\PhpNodes\PhpClassNode;
use Spatie\TypeScriptTransformer\PhpNodes\PhpMethodNode;
use Spatie\TypeScriptTransformer\TypeResolvers\Data\ParsedClass;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNode;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptObject;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptProperty;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptUnknown;

class ViewModelTransformer extends ClassListClassTransformer
{
    protected function shouldTransform(PhpClassNode $phpClassNode): bool
    {
        return is_subclass_of($phpClassNode->reflection->getName(), ViewModel::class);
    }

    protected function getTypeScriptNode(
        PhpClassNode $phpClassNode,
        TransformationContext $context,
        ?ParsedClass $parsedClass = null,
    ): TypeScriptNode {
        $typeScriptNode = parent::getTypeScriptNode($phpClassNode, $context, $parsedClass);

        if (! $typeScriptNode instanceof TypeScriptObject) {
            return $typeScriptNode;
        }

        return new TypeScriptObject([
            ...$typeScriptNode->properties,
            ...array_map(
                fn (PhpMethodNode $method): TypeScriptProperty => new TypeScriptProperty(
                    $method->getName(),
                    $this->resolveViewModelMethodType($phpClassNode, $method, $parsedClass),
                ),
                $this->getViewModelMethods($phpClassNode),
            ),
        ]);
    }

    /** @return array<PhpMethodNode> */
    private function getViewModelMethods(PhpClassNode $phpClassNode): array
    {
        return array_filter(
            $phpClassNode->getMethods(ReflectionMethod::IS_PUBLIC),
            fn (PhpMethodNode $method): bool => $method->getDeclaringClass()->reflection->getName() === $phpClassNode->reflection->getName()
                && count($method->getParameters()) === 0
                && ! $method->reflection->isConstructor()
                && ! $method->reflection->isStatic(),
        );
    }

    private function resolveViewModelMethodType(
        PhpClassNode $phpClassNode,
        PhpMethodNode $method,
        ?ParsedClass $parsedClass,
    ): TypeScriptNode {
        if ($returnType = $this->docTypeResolver->method($method)?->returnType) {
            return $this->transpilePhpStanTypeToTypeScriptTypeAction->execute(
                $returnType,
                $phpClassNode,
                $parsedClass->templates ?? [],
            );
        }

        if ($returnType = $method->getReturnType()) {
            return $this->transpilePhpTypeNodeToTypeScriptTypeAction->execute($returnType, $phpClassNode);
        }

        return new TypeScriptUnknown;
    }
}
