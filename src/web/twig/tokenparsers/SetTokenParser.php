<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\AssignGlobalExpression;
use craft\web\twig\nodes\SetGlobalNode;
use Twig\Error\SyntaxError;
use Twig\Lexer;
use Twig\Node\Expression\AssignNameExpression;
use Twig\Node\Node;
use Twig\Node\SetNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class SetTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.0
 */
class SetTokenParser extends AbstractTokenParser
{
    /**
     * @inheritdoc
     */
    public function parse(Token $token): Node
    {
        // Mostly copied from Twig\TokenParser\SetTokenParser, except with added support for setting globals
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        // See if it starts with "global", unless we are setting a variable called "global"
        $global = false;
        try {
            if ($stream->test(Token::NAME_TYPE, 'global')) {
                $nextToken = $stream->look();
                if (
                    // variable name is set to an operator name (`matches`, `in`, `is`, etc.)
                    // https://github.com/twigphp/Twig/commit/8d805aacb8f23cdf8ff7c91c4c6f7d16e04f3c3c
                    ($nextToken->test(Token::OPERATOR_TYPE) && preg_match(Lexer::REGEX_NAME, $token->getValue())) ||
                    $nextToken->test(Token::NAME_TYPE)
                ) {
                    $global = true;
                    $stream->next();
                }
            }
        } catch (SyntaxError) {
        }

        $names = $this->parser->getExpressionParser()->parseAssignmentExpression();

        if ($global) {
            // Swap AssignNameExpression nodes with AssignGlobalExpression nodes
            $names = new Node(array_map(
                fn(AssignNameExpression $node) => new AssignGlobalExpression($node->getAttribute('name'), $node->getTemplateLine()),
                iterator_to_array($names),
            ));
        }


        $capture = false;
        if ($stream->nextIf(Token::OPERATOR_TYPE, '=')) {
            $values = $this->parser->getExpressionParser()->parseMultitargetExpression();

            $stream->expect(Token::BLOCK_END_TYPE);

            if (count($names) !== count($values)) {
                throw new SyntaxError('When using set, you must have the same number of variables and assignments.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
            }
        } else {
            $capture = true;

            if (count($names) > 1) {
                throw new SyntaxError('When using set with a block, you cannot have a multi-target.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
            }

            $stream->expect(Token::BLOCK_END_TYPE);

            $values = $this->parser->subparse([$this, 'decideBlockEnd'], true);
            $stream->expect(Token::BLOCK_END_TYPE);
        }

        if ($global) {
            return new SetGlobalNode($capture, $names, $values, $lineno, $this->getTag());
        }

        return new SetNode($capture, $names, $values, $lineno, $this->getTag());
    }

    /**
     * @inheritdoc
     */
    public function decideBlockEnd(Token $token): bool
    {
        return $token->test('endset');
    }

    /**
     * @inheritdoc
     */
    public function getTag(): string
    {
        return 'set';
    }
}
