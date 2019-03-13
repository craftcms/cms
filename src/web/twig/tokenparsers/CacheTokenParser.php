<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig\tokenparsers;

use craft\web\twig\nodes\CacheNode;
use Twig\Parser;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Class CacheTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class CacheTokenParser extends AbstractTokenParser
{
    // Public Methods
    // =========================================================================

    /**
     * @return string
     */
    public function getTag(): string
    {
        return 'cache';
    }

    /**
     * @inheritdoc
     */
    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        /** @var Parser $parser */
        $parser = $this->parser;
        $stream = $parser->getStream();

        $nodes = [];

        $attributes = [
            'global' => false,
            'durationNum' => null,
            'durationUnit' => null,
        ];

        if ($stream->test(Token::NAME_TYPE, 'globally')) {
            $attributes['global'] = true;
            $stream->next();
        }

        if ($stream->test(Token::NAME_TYPE, 'using')) {
            $stream->next();
            $stream->expect(Token::NAME_TYPE, 'key');
            $nodes['key'] = $parser->getExpressionParser()->parseExpression();
        }

        if ($stream->test(Token::NAME_TYPE, 'for')) {
            $stream->next();
            $attributes['durationNum'] = $stream->expect(Token::NUMBER_TYPE)->getValue();
            $attributes['durationUnit'] = $stream->expect(Token::NAME_TYPE,
                [
                    'sec',
                    'secs',
                    'second',
                    'seconds',
                    'min',
                    'mins',
                    'minute',
                    'minutes',
                    'hour',
                    'hours',
                    'day',
                    'days',
                    'fortnight',
                    'fortnights',
                    'forthnight',
                    'forthnights',
                    'month',
                    'months',
                    'year',
                    'years',
                    'week',
                    'weeks'
                ])->getValue();
        } else if ($stream->test(Token::NAME_TYPE, 'until')) {
            $stream->next();
            $nodes['expiration'] = $parser->getExpressionParser()->parseExpression();
        }

        if ($stream->test(Token::NAME_TYPE, 'if')) {
            $stream->next();
            $nodes['conditions'] = $parser->getExpressionParser()->parseExpression();
        } else if ($stream->test(Token::NAME_TYPE, 'unless')) {
            $stream->next();
            $nodes['ignoreConditions'] = $parser->getExpressionParser()->parseExpression();
        }

        $stream->expect(Token::BLOCK_END_TYPE);
        $nodes['body'] = $parser->subparse([
            $this,
            'decideCacheEnd'
        ], true);
        $stream->expect(Token::BLOCK_END_TYPE);

        return new CacheNode($nodes, $attributes, $lineno, $this->getTag());
    }

    /**
     * @param Token $token
     * @return bool
     */
    public function decideCacheEnd(Token $token): bool
    {
        return $token->test('endcache');
    }
}
