<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\helpers\DateTimeHelper;
use craft\web\twig\nodes\ExpiresNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class ExpiresTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.2.0
 */
class ExpiresTokenParser extends AbstractTokenParser
{
    /**
     * @inheritdoc
     */
    public function parse(Token $token): ExpiresNode
    {
        $lineno = $token->getLine();
        $parser = $this->parser;
        $stream = $parser->getStream();

        $nodes = [];

        $attributes = [
            'durationNum' => 0,
            'durationUnit' => 'seconds',
        ];

        if ($stream->test(Token::OPERATOR_TYPE, 'in')) {
            $stream->next();
            $attributes['durationNum'] = $stream->expect(Token::NUMBER_TYPE)->getValue();
            $attributes['durationUnit'] = $stream->expect(Token::NAME_TYPE, DateTimeHelper::RELATIVE_TIME_UNITS)->getValue();
        } elseif ($stream->test(Token::NAME_TYPE, 'on')) {
            $stream->next();
            $nodes['expiration'] = $parser->getExpressionParser()->parseExpression();
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new ExpiresNode($nodes, $attributes, $lineno, $this->getTag());
    }

    /**
     * @inheritdoc
     */
    public function getTag(): string
    {
        return 'expires';
    }
}
