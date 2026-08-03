<?php

declare(strict_types=1);

namespace Workbench\App\TypeScript;

use CraftCms\Cms\Http\ViewModels\ViewModel;
use PHPStan\PhpDocParser\Ast\AbstractNodeVisitor;
use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\NodeTraverser;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
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
    #[\Override]
    protected function shouldTransform(PhpClassNode $phpClassNode): bool
    {
        return is_subclass_of($phpClassNode->reflection->getName(), ViewModel::class);
    }

    #[\Override]
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
            // Payload methods can be declared anywhere below the ViewModel base
            // (e.g. inherited from ContentIndexViewModel), so filter on the
            // declaring class being a ViewModel subclass rather than the
            // transformed class itself.
            fn (PhpMethodNode $method): bool => is_subclass_of($method->getDeclaringClass()->reflection->getName(), ViewModel::class)
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
                $this->substituteTypeAliases($returnType, $this->classTypeAliases($phpClassNode)),
                $phpClassNode,
                $parsedClass->templates ?? [],
            );
        }

        if ($returnType = $method->getReturnType()) {
            return $this->transpilePhpTypeNodeToTypeScriptTypeAction->execute($returnType, $phpClassNode);
        }

        return new TypeScriptUnknown;
    }

    /**
     * `@phpstan-type` aliases declared on the class, with references between
     * aliases already expanded. `@phpstan-import-type` is not supported: the
     * doc parser alone can't resolve the `from ClassName` reference against
     * the importing file's use statements.
     *
     * @return array<string, TypeNode>
     */
    private function classTypeAliases(PhpClassNode $phpClassNode): array
    {
        $docComment = $phpClassNode->getDocComment();

        if ($docComment === null) {
            return [];
        }

        $config = new ParserConfig(usedAttributes: []);
        $constExprParser = new ConstExprParser($config);
        $docParser = new PhpDocParser($config, new TypeParser($config, $constExprParser), $constExprParser);
        $docNode = $docParser->parse(new TokenIterator(new Lexer($config)->tokenize($docComment)));

        $aliases = [];

        foreach ($docNode->getTypeAliasTagValues() as $tag) {
            $aliases[$tag->alias] = $tag->type;
        }

        foreach ($aliases as $name => $type) {
            $aliases[$name] = $this->substituteTypeAliases($type, array_diff_key($aliases, [$name => null]));
        }

        return $aliases;
    }

    /** @param array<string, TypeNode> $aliases */
    private function substituteTypeAliases(TypeNode $type, array $aliases): TypeNode
    {
        if ($aliases === []) {
            return $type;
        }

        $visitor = new class($aliases) extends AbstractNodeVisitor
        {
            /** @param array<string, TypeNode> $aliases */
            public function __construct(
                private readonly array $aliases,
            ) {}

            public function enterNode(Node $node): ?Node
            {
                return $node instanceof IdentifierTypeNode
                    ? ($this->aliases[$node->name] ?? null)
                    : null;
            }
        };

        [$type] = new NodeTraverser([$visitor])->traverse([$type]);

        return $type;
    }
}
