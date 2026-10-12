<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support;

use Illuminate\Support\Arr;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;

class ArrWhereNotNullReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Arr::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'whereNotNull';
    }

    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
    {
        $argument = $methodCall->getArgs()[0] ?? null;

        if ($argument === null) {
            return null;
        }

        $type = $scope->getType($argument->value);

        if (! $type->isConstantArray()->yes()) {
            return null;
        }

        return TypeTraverser::map($type, static function (Type $type, callable $traverse): Type {
            if (! $type instanceof ConstantArrayType) {
                return $traverse($type);
            }

            $builder = ConstantArrayTypeBuilder::createEmpty();

            foreach ($type->getKeyTypes() as $index => $key) {
                $value = $type->getValueTypes()[$index];

                if ($value->isNull()->yes()) {
                    continue;
                }

                $builder->setOffsetValueType($key, TypeCombinator::removeNull($value), $type->isOptionalKey($index) || ! $value->isNull()->no());
            }

            if ($type->getUnsealedTypes() !== null) {
                [$key, $value] = $type->getUnsealedTypes();
                $builder->makeUnsealed($key, TypeCombinator::removeNull($value));
            }

            return $builder->getArray();
        });
    }
}
