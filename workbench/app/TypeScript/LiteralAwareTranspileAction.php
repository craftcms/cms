<?php

declare(strict_types=1);

namespace Workbench\App\TypeScript;

use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFalseNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFloatNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprNullNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprTrueNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use Spatie\TypeScriptTransformer\Actions\TranspilePhpStanTypeToTypeScriptNodeAction;
use Spatie\TypeScriptTransformer\PhpNodes\PhpClassNode;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptLiteral;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNode;

/**
 * Extends the stock PHPStan-doc transpiler with literal const types: the
 * upstream action only understands `ConstTypeNode` for enum-const generics,
 * so scalar literals in array shapes — the discriminants of tagged unions
 * like `array{mode: 'cards', …}|array{mode: 'index', …}` — fell through to
 * `unknown`. Here they become TypeScript literal types.
 */
class LiteralAwareTranspileAction extends TranspilePhpStanTypeToTypeScriptNodeAction
{
    #[\Override]
    protected function resolve(
        TypeNode $type,
        ?PhpClassNode $phpClassNode,
    ): TypeScriptNode {
        if ($type instanceof ConstTypeNode) {
            $literal = match ($type->constExpr::class) {
                ConstExprStringNode::class => new TypeScriptLiteral($type->constExpr->value),
                ConstExprIntegerNode::class => new TypeScriptLiteral((int) $type->constExpr->value),
                ConstExprFloatNode::class => new TypeScriptLiteral((float) $type->constExpr->value),
                ConstExprTrueNode::class => new TypeScriptLiteral(true),
                ConstExprFalseNode::class => new TypeScriptLiteral(false),
                ConstExprNullNode::class => new TypeScriptLiteral(null),
                default => null,
            };

            if ($literal !== null) {
                return $literal;
            }
        }

        return parent::resolve($type, $phpClassNode);
    }
}
