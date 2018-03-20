<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use Craft;
use craft\web\twig\nodes\RegisterResourceNode;

/**
 * Class RegisterResourceTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RegisterResourceTokenParser extends \Twig_TokenParser
{
    // Properties
    // =========================================================================

    /**
     * @var string The tag name
     */
    public $tag;

    /**
     * @var string The View method the tag represents
     */
    public $method;

    /**
     * @var bool Whether the tag supports a tag pair mode for capturing the JS/CSS
     */
    public $allowTagPair = false;

    /**
     * @var bool Whether the tag can specify the position of the resource
     */
    public $allowPosition = false;

    /**
     * @var bool Whether the tag can specify a runtime-based position (load/ready)
     */
    public $allowRuntimePosition = false;

    /**
     * @var bool Whether the tag can specify additional options
     */
    public $allowOptions = false;

    /**
     * @var string|null The new template code that should be used if this tag is deprecated
     * @todo Remove this in Craft 4
     */
    public $newCode;

    // Public Methods
    // =========================================================================

    /**
     * @param string $tag the tag name
     * @param string $method the View method the tag represents
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct(string $tag, string $method, array $config = [])
    {
        $this->tag = $tag;
        $this->method = $method;

        if (!empty($config)) {
            Craft::configure($this, $config);
        }
    }

    /**
     * @inheritdoc
     */
    public function parse(\Twig_Token $token)
    {
        // Is this the deprecated version?
        if ($this->newCode !== null) {
            \Craft::$app->getDeprecator()->log($this->tag, "{% {$this->tag} %} is now deprecated. Use {$this->newCode} instead.");
        }

        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $expressionParser = $this->parser->getExpressionParser();
        $nodes = [];

        // Is this a tag pair?
        if (
            $this->allowTagPair &&
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
            $nodes['value'] = $expressionParser->parseExpression();
        }

        // Is there a position param?
        if ($this->allowPosition && $stream->test(\Twig_Token::NAME_TYPE, 'at')) {
            $stream->next();
            $nameToken = $stream->expect(\Twig_Token::NAME_TYPE,
                ['head', 'beginBody', 'endBody']);
            $position = $nameToken->getValue();
        } else if ($this->allowRuntimePosition && $stream->test(\Twig_Token::NAME_TYPE, 'on')) {
            $stream->next();
            $nameToken = $stream->expect(\Twig_Token::NAME_TYPE,
                ['ready', 'load']);
            $position = $nameToken->getValue();
        } else {
            $position = null;
        }

        // Is there an options param?
        if ($this->allowOptions && $stream->test(\Twig_Token::NAME_TYPE, 'with')) {
            $stream->next();
            $nodes['options'] = $expressionParser->parseExpression();
        }

        $first = $this->_getFirstValue($stream);

        // Close out the tag
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        if ($capture) {
            // Tag pair. Capture the value.
            $nodes['value'] = $this->parser->subparse([$this, 'decideBlockEnd'], true);
            $stream->expect(\Twig_Token::BLOCK_END_TYPE);
        }

        // Pass everything off to the RegisterResourceNode
        $attributes = [
            'method' => $this->method,
            'allowOptions' => $this->allowOptions,
            'allowPosition' => $this->allowPosition,
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
        return $this->tag;
    }

    /**
     * @param \Twig_Token $token
     * @return bool
     */
    public function decideBlockEnd(\Twig_Token $token): bool
    {
        return $token->test('end'.strtolower($this->tag));
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns whether the next token in the stream is a position param.
     *
     * @param \Twig_TokenStream $stream The Twig token stream
     * @return bool
     */
    private function _testPositionParam(\Twig_TokenStream $stream): bool
    {
        return (
            ($this->allowPosition && $stream->test(\Twig_Token::NAME_TYPE, 'at')) ||
            ($this->allowRuntimePosition && $stream->test(\Twig_Token::NAME_TYPE, 'on'))
        );
    }

    /**
     * Returns whether the next token in the stream is an options param.
     *
     * @param \Twig_TokenStream $stream The Twig token stream
     * @return bool
     */
    private function _testOptionsParam(\Twig_TokenStream $stream): bool
    {
        return ($this->allowOptions && $stream->test(\Twig_Token::NAME_TYPE, 'with'));
    }

    /**
     * Returns whether the next token in the stream is the deprecated `first` param
     *
     * @param \Twig_TokenStream $stream The Twig token stream
     * @return bool
     */
    private function _testFirstParam(\Twig_TokenStream $stream): bool
    {
        return ($this->newCode !== null && $first = $stream->test(\Twig_Token::NAME_TYPE, 'first'));
    }

    /**
     * Returns whether the next token in the stream is the deprecated `first` param.
     *
     * @param \Twig_TokenStream $stream The Twig token stream
     * @return bool
     */
    private function _getFirstValue(\Twig_TokenStream $stream): bool
    {
        if ($this->_testFirstParam($stream)) {
            $stream->next();

            return true;
        }

        return false;
    }
}
