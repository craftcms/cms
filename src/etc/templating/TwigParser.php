<?php
namespace Craft;

/**
 * TwigParser class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.templating.twigextensions
 * @since     2.4
 */
class TwigParser extends \Twig_Parser
{
	// Protected Methods
	// =========================================================================

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
