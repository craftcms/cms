<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\NodeVisitors;

use CraftCms\Cms\Twig\Nodes\FallbackNameExpression;
use CraftCms\Cms\Twig\Nodes\PreloadSinglesNode;
use Twig\Environment;
use Twig\Node\BodyNode;
use Twig\Node\Expression\AssignNameExpression;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\MacroNode;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * SinglePreloader preloads Single section entries for a template.
 */
final class SinglePreloader implements NodeVisitorInterface
{
    /**
     * @var array<string,bool>[]
     */
    private array $foundVariables = [];

    public function enterNode(Node $node, Environment $env): Node
    {
        if ($this->isRelevant($node)) {
            array_unshift($this->foundVariables, []);
        } elseif (
            ! empty($this->foundVariables) &&
            $node instanceof ContextVariable &&
            ! $node instanceof AssignNameExpression &&
            $node->hasAttribute('name') &&
            ! $node->getAttribute('always_defined') &&
            (! $node->hasAttribute('spread') || ! $node->getAttribute('spread'))
        ) {
            $variables = &$this->foundVariables[0];
            $variables[$node->getAttribute('name')] = true;

            $isDefinedTest = $node->isDefinedTestEnabled();

            // swap the node with a FallbackNameExpression
            $node = new FallbackNameExpression($node->getAttribute('name'), [
                'is_defined_test' => $isDefinedTest,
                'ignore_strict_check' => $node->getAttribute('ignore_strict_check'),
            ], $node->getTemplateLine());

            if ($isDefinedTest) {
                $node->enableDefinedTest();
            }
        }

        return $node;
    }

    public function leaveNode(Node $node, Environment $env): \Twig\Node\Node
    {
        if (! $this->isRelevant($node)) {
            return $node;
        }

        $variables = array_shift($this->foundVariables);

        if (empty($variables)) {
            return $node;
        }

        if (! $node->hasNode('body')) {
            return $node;
        }

        $body = $node->getNode('body');

        if (! $body instanceof BodyNode) {
            return $node;
        }

        /** @var Node[] $subNodes */
        $subNodes = iterator_to_array($body);
        foreach (array_keys($subNodes) as $key) {
            $body->removeNode((string) $key);
        }

        array_unshift($subNodes, new PreloadSinglesNode(attributes: [
            'handles' => array_keys($variables),
        ]));

        foreach ($subNodes as $key => $subNode) {
            $body->setNode($key, $subNode);
        }

        return $node;
    }

    private function isRelevant(Node $node): bool
    {
        return
            $node instanceof ModuleNode ||
            $node instanceof MacroNode;
    }

    public function getPriority(): int
    {
        return 0;
    }
}
