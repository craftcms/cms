<?php
namespace Craft;

/**
 * Cache twig node.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.templating.twigextensions
 * @since     2.4
 */
class TwigParser extends \Twig_Parser
{
	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc \Twig_Parser::filterBodyNodes()
	 */
	protected function filterBodyNodes(\Twig_NodeInterface $node)
	{
		// Bypass "include" nodes as they "capture" the output
		if ($node instanceof IncludeResource_Node)
		{
			return $node;
		}

		return parent::filterBodyNodes($node);
	}
}
