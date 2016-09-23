<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\twig\tokenparsers;

use craft\app\web\twig\nodes\RegisterResourceNode;

/**
 * Class RegisterResourceTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class RegisterResourceTokenParser extends \Twig_TokenParser
{
    // Properties
    // =========================================================================

    /**
     * @var string The tag name
     */
    private $_tag;

    /**
     * @var string The View method the tag represents
     */
    private $_method;

    /**
     * @var boolean Whether the tag supports a tag pair mode for capturing the JS/CSS
     */
    private $_allowTagPair;

    /**
     * @var boolean Whether the tag can specify the position of the resource
     */
    private $_allowPosition;

    /**
     * @var boolean Whether the tag can specify a runtime-based position (load/ready)
     */
    private $_allowRuntimePosition;

    /**
     * @var boolean Whether the tag can specify additional options
     */
    private $_allowOptions;

    /**
     * @var string The new tag name that should be used if this one is deprecated
     */
    private $_newTag;

    // Public Methods
    // =========================================================================

    /**
     * @param string  $tag                  The tag name
     * @param string  $method               The View method the tag represents
     * @param boolean $allowTagPair         Whether the tag supports a tag pair mode for capturing the JS/CSS
     * @param boolean $allowPosition        Whether the tag can specify the position of the resource
     * @param boolean $allowRuntimePosition Whether the tag can specify a runtime-based position (load/ready)
     * @param boolean $allowOptions         Whether the tag can specify additional options
     * @param string  $newTag               The new tag name that should be used if this one is deprecated
     *
     * @todo Remove the $newTag stuff in Craft 4
     */
    public function __construct($tag, $method, $allowTagPair = false, $allowPosition = false, $allowRuntimePosition = false, $allowOptions = false, $newTag = null)
    {
        $this->_tag = $tag;
        $this->_method = $method;
        $this->_allowTagPair = $allowTagPair;
        $this->_allowPosition = $allowPosition;
        $this->_allowRuntimePosition = $allowRuntimePosition;
        $this->_allowOptions = $allowOptions;
        $this->_newTag = $newTag;
    }

    /**
     * @inheritdoc
     */
    public function parse(\Twig_Token $token)
    {
        // Is this the deprecated version?
        if ($isDeprecated = ($this->_newTag !== null)) {
            \Craft::$app->getDeprecator()->log($this->_tag, "{% $this->_tag %} is now deprecated. Use {% $this->_newTag %} instead.");
        }

        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $expressionParser = $this->parser->getExpressionParser();

        // Is this a tag pair?
        if (
            $this->_allowTagPair &&
            (
                $this->_testPositionParam($stream) ||
                $this->_testOptionsParam($stream) ||
                $this->_testFirstParam($stream) ||
                $stream->test(\Twig_Token::BLOCK_END_TYPE)
            )
        ) {
            $capture = true;
        } else {
            $capture = false;
            $value = $expressionParser->parseExpression();
        }

        // Is there a position param?
        if ($this->_allowPosition && $stream->test(\Twig_Token::NAME_TYPE,
                'at')
        ) {
            $stream->next();
            $nameToken = $stream->expect(\Twig_Token::NAME_TYPE,
                ['head', 'beginBody', 'endBody']);
            $position = $nameToken->getValue();
        } else if ($this->_allowRuntimePosition && $stream->test(\Twig_Token::NAME_TYPE,
                'on')
        ) {
            $stream->next();
            $nameToken = $stream->expect(\Twig_Token::NAME_TYPE,
                ['ready', 'load']);
            $position = $nameToken->getValue();
        } else {
            $position = null;
        }

        // Is there an options param?
        if ($this->_allowOptions && $stream->test(\Twig_Token::NAME_TYPE,
                'with')
        ) {
            $stream->next();
            $options = $expressionParser->parseExpression();
        } else {
            $options = null;
        }

        $first = $this->_getFirstValue($stream);

        // Close out the tag
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        if ($capture) {
            // Tag pair. Capture the value.
            $value = $this->parser->subparse([$this, 'decideBlockEnd'], true);
            $stream->expect(\Twig_Token::BLOCK_END_TYPE);
        }

        // Pass everything off to the RegisterResourceNode
        /** @noinspection PhpUndefinedVariableInspection */
        $nodes = [
            'value' => $value,
            'options' => $options
        ];

        $attributes = [
            'method' => $this->_method,
            'allowOptions' => $this->_allowOptions,
            'allowPosition' => $this->_allowPosition,
            'capture' => $capture,
            'position' => $position,
            'first' => $first
        ];

        return new RegisterResourceNode($nodes, $attributes, $lineno, $this->getTag());
    }

    /**
     * @inheritdoc
     */
    public function getTag()
    {
        return $this->_tag;
    }

    /**
     * @param \Twig_Token $token
     *
     * @return boolean
     */
    public function decideBlockEnd(\Twig_Token $token)
    {
        return $token->test('end'.strtolower($this->_tag));
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns whether the next token in the stream is a position param.
     *
     * @param \Twig_TokenStream $stream The Twig token stream
     *
     * @return boolean
     */
    private function _testPositionParam(\Twig_TokenStream $stream)
    {
        return (
            ($this->_allowPosition && $stream->test(\Twig_Token::NAME_TYPE,
                    'at')) ||
            ($this->_allowRuntimePosition && $stream->test(\Twig_Token::NAME_TYPE,
                    'on'))
        );
    }

    /**
     * Returns whether the next token in the stream is an options param.
     *
     * @param \Twig_TokenStream $stream The Twig token stream
     *
     * @return boolean
     */
    private function _testOptionsParam(\Twig_TokenStream $stream)
    {
        return ($this->_allowOptions && $stream->test(\Twig_Token::NAME_TYPE,
                'with'));
    }

    /**
     * Returns whether the next token in the stream is the deprecated `first` param
     *
     * @param \Twig_TokenStream $stream The Twig token stream
     *
     * @return boolean
     */
    private function _testFirstParam(\Twig_TokenStream $stream)
    {
        return ($this->_newTag !== null && $first = $stream->test(\Twig_Token::NAME_TYPE,
                'first'));
    }

    /**
     * Returns whether the next token in the stream is the deprecated `first` param.
     *
     * @param \Twig_TokenStream $stream The Twig token stream
     *
     * @return boolean
     */
    private function _getFirstValue(\Twig_TokenStream $stream)
    {
        if ($this->_testFirstParam($stream)) {
            $stream->next();

            return true;
        }

        return false;
    }
}
