<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\nodevisitors;

use craft\web\twig\nodes\PreloadSinglesNode;
use Twig\Environment;
use Twig\Node\BodyNode;
use Twig\Node\Expression\NameExpression;
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
     * @var array<string,bool>
     */
    private array $_foundVariables;

    /**
     * @inheritdoc
     */
    public function enterNode(Node $node, Environment $env): Node
    {
        if ($node instanceof ModuleNode) {
            $this->_foundVariables = [];
        } elseif (get_class($node) === NameExpression::class && $node->hasAttribute('name')) {
            $this->_foundVariables[$node->getAttribute('name')] = true;
        }
        return $node;
    }

    /**
     * @inheritdoc
     */
    public function leaveNode(Node $node, Environment $env): ?Node
    {
        if (
            $node instanceof ModuleNode &&
            !empty($this->_foundVariables) &&
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
                    'handles' => array_keys($this->_foundVariables),
                ]));
                foreach ($subNodes as $key => $subNode) {
                    $body->setNode($key, $subNode);
                }
            }
        }
        return $node;
    }

    /**
     * @inheritdoc
     */
    public function getPriority(): int
    {
        return 0;
    }
}
