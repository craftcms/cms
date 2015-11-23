<?php
namespace Craft;

/**
 * Paginates elements via a ElementCriteriaModel instance.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.templating.twigextensions
 * @since     1.0
 */
class Paginate_TokenParser extends \Twig_TokenParser
{
	// Public Methods
	// =========================================================================

	/**
	 * Parses a token and returns a node.
	 *
	 * @param \Twig_Token $token
	 *
	 * @return \Twig_NodeInterface
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();

		$nodes['criteria'] = $this->parser->getExpressionParser()->parseExpression();
		$this->parser->getStream()->expect('as');
		$targets = $this->parser->getExpressionParser()->parseAssignmentExpression();
		$this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

		if (count($targets) > 1)
		{
		    $paginateTarget = $targets->getNode(0);
		    $nodes['paginateTarget'] = new \Twig_Node_Expression_AssignName($paginateTarget->getAttribute('name'), $paginateTarget->getLine());
		    $elementsTarget = $targets->getNode(1);

		}
		else
		{
		    $nodes['paginateTarget'] = new \Twig_Node_Expression_AssignName('paginate', $lineno);
		    $elementsTarget = $targets->getNode(0);
		}

		$nodes['elementsTarget'] = new \Twig_Node_Expression_AssignName($elementsTarget->getAttribute('name'), $elementsTarget->getLine());

		return new Paginate_Node($nodes, array(), $lineno, $this->getTag());
	}

	/**
	 * Gets the tag name associated with this token parser.
	 *
	 * @return string The tag name
	 */
	public function getTag()
	{
		return 'paginate';
	}
}
