<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Class_>
 */
class RequireSinceTagRule implements Rule
{
    public function __construct(private readonly string $sourceDirectory) {}

    public function getNodeType(): string
    {
        return Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->isAnonymous() || ! str_starts_with($scope->getFile(), rtrim($this->sourceDirectory, '/\\').DIRECTORY_SEPARATOR)) {
            return [];
        }

        $docComment = $node->getDocComment();

        if ($docComment !== null && preg_match('/^(?:[ \t]*\/\*\*|[ \t]*\*)[ \t]*@since[ \t]+[^\s*]/m', $docComment->getText())) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Class %s must have a non-empty @since tag in its docblock.',
                $node->name->toString(),
            ))
                ->identifier('craft.classSince')
                ->build(),
        ];
    }
}
