<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web\twig\tokenparsers;

use craft\app\web\twig\nodes\RequireAdminNode;

/**
 * Class RequireAdminTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RequireAdminTokenParser extends \Twig_TokenParser
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function parse(\Twig_Token $token)
	{
		$lineno = $token->getLine();
		$this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

		return new RequireAdminNode([], [], $lineno, $this->getTag());
	}

	/**
	 * @inheritdoc
	 */
	public function getTag()
	{
		return 'requireAdmin';
	}
}
