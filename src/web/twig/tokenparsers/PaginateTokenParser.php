<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\PaginateNode;
use Twig\Node\Expression\AssignNameExpression;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Paginates elements via an [[\craft\elements\db\ElementQuery]] instance.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class PaginateTokenParser extends AbstractTokenParser
{
    /**
     * @inheritdoc
     */
    public function parse(Token $token): PaginateNode
    {
        $lineno = $token->getLine();
        $parser = $this->parser;
        $stream = $parser->getStream();

        $nodes = [
            'query' => $parser->getExpressionParser()->parseExpression(),
        ];
        $stream->expect('as');
        $targets = $parser->getExpressionParser()->parseAssignmentExpression();
        $stream->expect(Token::BLOCK_END_TYPE);

        if (count($targets) > 1) {
            $infoVariable = $targets->getNode(0);
            $nodes['infoVariable'] = new AssignNameExpression($infoVariable->getAttribute('name'), $infoVariable->getTemplateLine());
            $resultVariable = $targets->getNode(1);
        } else {
            $nodes['infoVariable'] = new AssignNameExpression('paginate', $lineno);
            $resultVariable = $targets->getNode(0);
        }

        $nodes['resultVariable'] = new AssignNameExpression($resultVariable->getAttribute('name'), $resultVariable->getTemplateLine());

        return new PaginateNode($nodes, [], $lineno, $this->getTag());
    }

    /**
     * @inheritdoc
     */
    public function getTag(): string
    {
        return 'paginate';
    }
}
