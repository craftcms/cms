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
 * @since 3.0
 */
class PaginateTokenParser extends AbstractTokenParser
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function parse(Token $token)
    {
        $lineno = $token->getLine();

        $nodes = [
            'criteria' => $this->parser->getExpressionParser()->parseExpression()
        ];
        $this->parser->getStream()->expect('as');
        $targets = $this->parser->getExpressionParser()->parseAssignmentExpression();
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        if (count($targets) > 1) {
            $paginateTarget = $targets->getNode(0);
            $nodes['paginateTarget'] = new AssignNameExpression($paginateTarget->getAttribute('name'), $paginateTarget->getTemplateLine());
            $elementsTarget = $targets->getNode(1);
        } else {
            $nodes['paginateTarget'] = new AssignNameExpression('paginate', $lineno);
            $elementsTarget = $targets->getNode(0);
        }

        $nodes['elementsTarget'] = new AssignNameExpression($elementsTarget->getAttribute('name'), $elementsTarget->getTemplateLine());

        return new PaginateNode($nodes, [], $lineno, $this->getTag());
    }

    /**
     * @inheritdoc
     */
    public function getTag()
    {
        return 'paginate';
    }
}
