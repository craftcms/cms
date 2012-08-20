<?php
namespace Blocks;

/**
 *
 */
class IncludeTranslation_TokenParser extends \Twig_TokenParser
{
	/**
	 * Parses {% include_translation %} tags.
	 *
	 * @param \Twig_Token $token
	 * @return IncludeTranslation_Node
	 */
    public function parse(\Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $messages = array();

        while (true)
        {
        	$messages[] = $this->parser->getExpressionParser()->parseExpression();

        	if (!$stream->test(\Twig_Token::PUNCTUATION_TYPE, ','))
                break;

            $this->parser->getStream()->next();
        }

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        return new IncludeTranslation_Node($messages, array(), $lineno, $this->getTag());
    }

    /**
     * Defines the tag name.
     *
     * @return string
     */
    public function getTag()
    {
        return 'include_translation';
    }
}
