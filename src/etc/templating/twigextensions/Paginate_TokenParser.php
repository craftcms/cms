<?php
namespace Craft;

/**
 * Paginates entities via a ElementCriteriaModel instance.
 *
 * @package craft.app.etc.templating.twigextensions
 */
class Paginate_TokenParser extends \Twig_TokenParser
{
	/**
	 * Parses a token and returns a node.
	 *
	 * @param \Twig_Token $token
	 * @return \Twig_NodeInterface
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();

		$nodes['criteria'] = $this->parser->getExpressionParser()->parseExpression();
		$this->parser->getStream()->expect('as');
		$targets = $this->parser->getExpressionParser()->parseAssignmentExpression();
		$this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

		$nodes['body'] = $this->parser->subparse(array($this, 'decidePaginateEnd'), true);
		$this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

		$entitiesTarget = $targets->getNode(0);
		$nodes['entitiesTarget'] = new \Twig_Node_Expression_AssignName($entitiesTarget->getAttribute('name'), $entitiesTarget->getLine());

		return new Paginate_Node($nodes, array(), $lineno, $this->getTag());
	}

	/**
	 * @param \Twig_Token $token
	 * @return bool
	 */
	public function decidePaginateEnd(\Twig_Token $token)
	{
		return $token->test('endpaginate');
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
