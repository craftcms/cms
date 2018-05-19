<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\NavNode;

/**
 * Recursively outputs a hierarchical navigation.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class NavTokenParser extends \Twig_TokenParser
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function getTag()
    {
        return 'nav';
    }

    /**
     * @inheritdoc
     */
    public function parse(\Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $targets = $this->parser->getExpressionParser()->parseAssignmentExpression();
        $stream->expect(\Twig_Token::OPERATOR_TYPE, 'in');
        $seq = $this->parser->getExpressionParser()->parseExpression();
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        $upperBody = $this->parser->subparse([$this, 'decideNavFork']);
        $lowerBody = new \Twig_Node();
        $indent = new \Twig_Node();
        $outdent = new \Twig_Node();

        $nextValue = $stream->next()->getValue();

        if ($nextValue !== 'endnav') {
            $stream->expect(\Twig_Token::BLOCK_END_TYPE);

            if ($nextValue === 'ifchildren') {
                $indent = $this->parser->subparse([
                    $this,
                    'decideChildrenFork'
                ], true);
                $stream->expect(\Twig_Token::BLOCK_END_TYPE);
                $outdent = $this->parser->subparse([
                    $this,
                    'decideChildrenEnd'
                ], true);
                $stream->expect(\Twig_Token::BLOCK_END_TYPE);
            }

            $lowerBody = $this->parser->subparse([$this, 'decideNavEnd'], true);
        }

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        if (count($targets) > 1) {
            $keyTarget = $targets->getNode(0);
            $keyTarget = new \Twig_Node_Expression_AssignName($keyTarget->getAttribute('name'), $keyTarget->getTemplateLine());
            $valueTarget = $targets->getNode(1);
            $valueTarget = new \Twig_Node_Expression_AssignName($valueTarget->getAttribute('name'), $valueTarget->getTemplateLine());
        } else {
            $keyTarget = new \Twig_Node_Expression_AssignName('_key', $lineno);
            $valueTarget = $targets->getNode(0);
            $valueTarget = new \Twig_Node_Expression_AssignName($valueTarget->getAttribute('name'), $valueTarget->getTemplateLine());
        }

        return new NavNode($keyTarget, $valueTarget, $seq, $upperBody, $lowerBody, $indent, $outdent, $lineno, $this->getTag());
    }

    /**
     * @param \Twig_Token $token
     * @return bool
     */
    public function decideNavFork(\Twig_Token $token): bool
    {
        return $token->test(['ifchildren', 'children', 'endnav']);
    }

    /**
     * @param \Twig_Token $token
     * @return bool
     */
    public function decideChildrenFork(\Twig_Token $token): bool
    {
        return $token->test('children');
    }

    /**
     * @param \Twig_Token $token
     * @return bool
     */
    public function decideChildrenEnd(\Twig_Token $token): bool
    {
        return $token->test('endifchildren');
    }

    /**
     * @param \Twig_Token $token
     * @return bool
     */
    public function decideNavEnd(\Twig_Token $token): bool
    {
        return $token->test('endnav');
    }
}
