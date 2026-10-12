<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support;

use Illuminate\Support\Collection;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class CollectionValuesReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Collection::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'all';
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
    {
        $receiver = $methodCall->var;

        if (! $receiver instanceof MethodCall || ! $receiver->name instanceof Identifier || $receiver->name->toLowerString() !== 'values') {
            return null;
        }

        return TypeCombinator::intersect(
            new ArrayType(new IntegerType, $scope->getType($receiver)->getIterableValueType()),
            new AccessoryArrayListType,
        );
    }
}
