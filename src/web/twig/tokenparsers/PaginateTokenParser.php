<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\tokenparsers;

use craft\app\web\twig\nodes\PaginateNode;

/**
 * Paginates elements via a ElementCriteriaModel instance.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class PaginateTokenParser extends \Twig_TokenParser
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();

		$nodes['criteria'] = $this->parser->getExpressionParser()->parseExpression();
		$this->parser->getStream()->expect('as');
		$targets = $this->parser->getExpressionParser()->parseAssignmentExpression();
		$this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

		$nodes['body'] = $this->parser->subparse([$this, 'decidePaginateEnd'], true);
		$this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

		$elementsTarget = $targets->getNode(0);
		$nodes['elementsTarget'] = new \Twig_Node_Expression_AssignName($elementsTarget->getAttribute('name'), $elementsTarget->getLine());

		return new PaginateNode($nodes, [], $lineno, $this->getTag());
	}

	/**
	 * @param \Twig_Token $token
	 *
	 * @return bool
	 */
	public function decidePaginateEnd(\Twig_Token $token)
	{
		return $token->test('endpaginate');
	}

	/**
	 * @inheritdoc
	 */
	public function getTag()
	{
		return 'paginate';
	}
}
