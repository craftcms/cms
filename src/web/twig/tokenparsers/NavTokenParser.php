<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\NavNode;
use Twig\Node\Expression\AssignNameExpression;
use Twig\Node\Node;
use Twig\Parser;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Recursively outputs a hierarchical navigation.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class NavTokenParser extends AbstractTokenParser
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
    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        /** @var Parser $parser */
        $parser = $this->parser;
        $stream = $parser->getStream();

        $targets = $parser->getExpressionParser()->parseAssignmentExpression();
        $stream->expect(Token::OPERATOR_TYPE, 'in');
        $seq = $parser->getExpressionParser()->parseExpression();
        $stream->expect(Token::BLOCK_END_TYPE);

        $upperBody = $parser->subparse([$this, 'decideNavFork']);
        $lowerBody = new Node();
        $indent = new Node();
        $outdent = new Node();

        $nextValue = $stream->next()->getValue();

        if ($nextValue !== 'endnav') {
            $stream->expect(Token::BLOCK_END_TYPE);

            if ($nextValue === 'ifchildren') {
                $indent = $parser->subparse([
                    $this,
                    'decideChildrenFork'
                ], true);
                $stream->expect(Token::BLOCK_END_TYPE);
                $outdent = $parser->subparse([
                    $this,
                    'decideChildrenEnd'
                ], true);
                $stream->expect(Token::BLOCK_END_TYPE);
            }

            $lowerBody = $parser->subparse([$this, 'decideNavEnd'], true);
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        if (count($targets) > 1) {
            $keyTarget = $targets->getNode(0);
            $keyTarget = new AssignNameExpression($keyTarget->getAttribute('name'), $keyTarget->getTemplateLine());
            $valueTarget = $targets->getNode(1);
            $valueTarget = new AssignNameExpression($valueTarget->getAttribute('name'), $valueTarget->getTemplateLine());
        } else {
            $keyTarget = new AssignNameExpression('_key', $lineno);
            $valueTarget = $targets->getNode(0);
            $valueTarget = new AssignNameExpression($valueTarget->getAttribute('name'), $valueTarget->getTemplateLine());
        }

        return new NavNode($keyTarget, $valueTarget, $seq, $upperBody, $lowerBody, $indent, $outdent, $lineno, $this->getTag());
    }

    /**
     * @param Token $token
     * @return bool
     */
    public function decideNavFork(Token $token): bool
    {
        return $token->test(['ifchildren', 'children', 'endnav']);
    }

    /**
     * @param Token $token
     * @return bool
     */
    public function decideChildrenFork(Token $token): bool
    {
        return $token->test('children');
    }

    /**
     * @param Token $token
     * @return bool
     */
    public function decideChildrenEnd(Token $token): bool
    {
        return $token->test('endifchildren');
    }

    /**
     * @param Token $token
     * @return bool
     */
    public function decideNavEnd(Token $token): bool
    {
        return $token->test('endnav');
    }
}
