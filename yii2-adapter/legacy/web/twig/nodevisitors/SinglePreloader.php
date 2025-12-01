<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodevisitors;

use craft\web\twig\nodes\FallbackNameExpression;
use craft\web\twig\nodes\PreloadSinglesNode;
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
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class SinglePreloader implements NodeVisitorInterface
{
    /**
     * @var array<string,bool>[]
     */
    private array $_foundVariables = [];

    /**
     * @inheritdoc
     */
    public function enterNode(Node $node, Environment $env): Node
    {
        if ($this->isRelevant($node)) {
            array_unshift($this->_foundVariables, []);
        } elseif (
            !empty($this->_foundVariables) &&
            $node instanceof ContextVariable &&
            !$node instanceof AssignNameExpression &&
            $node->hasAttribute('name') &&
            !$node->getAttribute('always_defined') &&
            (!$node->hasAttribute('spread') || !$node->getAttribute('spread'))
        ) {
            $variables = &$this->_foundVariables[0];
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

    /**
     * @inheritdoc
     */
    public function leaveNode(Node $node, Environment $env): ?Node
    {
        if ($this->isRelevant($node)) {
            $variables = array_shift($this->_foundVariables);
            if (
                !empty($variables) &&
                $node->hasNode('body')
            ) {
                $body = $node->getNode('body');
                if ($body instanceof BodyNode) {
                    /** @var Node[] $subNodes */
                    $subNodes = iterator_to_array($body);
                    foreach (array_keys($subNodes) as $key) {
                        $body->removeNode((string)$key);
                    }
                    array_unshift($subNodes, new PreloadSinglesNode(attributes: [
                        'handles' => array_keys($variables),
                    ]));
                    foreach ($subNodes as $key => $subNode) {
                        $body->setNode($key, $subNode);
                    }
                }
            }
        }

        return $node;
    }

    private function isRelevant(Node $node): bool
    {
        return (
            $node instanceof ModuleNode ||
            $node instanceof MacroNode
        );
    }

    /**
     * @inheritdoc
     */
    public function getPriority(): int
    {
        return 0;
    }
}
